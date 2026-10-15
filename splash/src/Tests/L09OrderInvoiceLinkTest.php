<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local\Tests;

use Exception;
use Facture;
use PHPUnit\Framework\Assert;
use Splash\Client\Splash;
use Splash\Local\Objects\Invoice;
use Splash\Tests\Tools\ObjectsCase;

/**
 * Local Test Suite - Verify Reading of Order First Valid Invoice Number & Pdf
 */
class L09OrderInvoiceLinkTest extends ObjectsCase
{
    /**
     * Test Order without any Linked Invoice
     *
     * @throws Exception
     *
     * @return void
     */
    public function testOrderWithoutInvoice(): void
    {
        $this->loadLocalTestSequence("Monolangual");

        $orderId = $this->createOrder();
        //====================================================================//
        // No Invoice Linked => Both Fields are Empty
        Assert::assertEmpty($this->readField($orderId, "invoiceNumber"));
        Assert::assertEmpty($this->readField($orderId, "invoicePdf"));
    }

    /**
     * Test Order Linked to a Draft Invoice Only
     *
     * @throws Exception
     *
     * @return void
     */
    public function testDraftInvoiceIsIgnored(): void
    {
        $this->loadLocalTestSequence("Monolangual");

        $orderId = $this->createOrder();
        $this->createLinkedInvoice($orderId, false);
        //====================================================================//
        // Draft Invoices are Never Exposed
        Assert::assertEmpty($this->readField($orderId, "invoiceNumber"));
        Assert::assertEmpty($this->readField($orderId, "invoicePdf"));
    }

    /**
     * Test Order Linked to a Validated Invoice
     *
     * Dolibarr generates the Invoice Pdf on validation.
     *
     * @throws Exception
     *
     * @return void
     */
    public function testValidatedInvoiceIsExposed(): void
    {
        $this->loadLocalTestSequence("Monolangual");

        $orderId = $this->createOrder();
        $invoice = $this->createLinkedInvoice($orderId, true);
        //====================================================================//
        // Invoice Number is Exposed
        Assert::assertSame($invoice->ref, $this->readField($orderId, "invoiceNumber"));
        //====================================================================//
        // Invoice Pdf is Exposed as a Stream
        $stream = $this->readField($orderId, "invoicePdf");
        Assert::assertIsArray($stream);
        Assert::assertArrayHasKey("filename", $stream);
        Assert::assertArrayHasKey("md5", $stream);
        Assert::assertSame(dol_sanitizeFileName((string) $invoice->ref).".pdf", $stream["filename"]);
        Assert::assertNotEmpty($stream["md5"]);
    }

    /**
     * Test Invoice without any Generated Pdf
     *
     * Number & Pdf are independent: a Number without Pdf is a valid state.
     *
     * @throws Exception
     *
     * @return void
     */
    public function testInvoiceWithoutPdf(): void
    {
        $this->loadLocalTestSequence("Monolangual");

        $orderId = $this->createOrder();
        $invoice = $this->createLinkedInvoice($orderId, true);
        //====================================================================//
        // Remove the Generated Pdf
        $this->removeInvoicePdf($invoice);
        //====================================================================//
        // Number is Still Exposed, Pdf is Not
        Assert::assertSame($invoice->ref, $this->readField($orderId, "invoiceNumber"));
        Assert::assertEmpty($this->readField($orderId, "invoicePdf"));
    }

    /**
     * Test Only the Invoice Pdf is Exposed, not any Attached File
     *
     * @throws Exception
     *
     * @return void
     */
    public function testOnlyInvoicePdfIsExposed(): void
    {
        $this->loadLocalTestSequence("Monolangual");

        $orderId = $this->createOrder();
        $invoice = $this->createLinkedInvoice($orderId, true);
        $fileDir = $this->getInvoiceFilesDir($invoice);
        //====================================================================//
        // Remove the Generated Pdf & Attach an Unrelated File instead
        $this->removeInvoicePdf($invoice);
        file_put_contents($fileDir."/any-attached-file.pdf", "NOT THE INVOICE");
        Assert::assertEmpty(
            $this->readField($orderId, "invoicePdf"),
            "An attached file must never be exposed as the Invoice Pdf"
        );
        //====================================================================//
        // Restore the Invoice Pdf itself
        $pdfName = dol_sanitizeFileName((string) $invoice->ref).".pdf";
        file_put_contents($fileDir."/".$pdfName, "%PDF-1.4 FAKE INVOICE");
        $stream = $this->readField($orderId, "invoicePdf");
        Assert::assertIsArray($stream);
        Assert::assertArrayHasKey("filename", $stream);
        Assert::assertSame($pdfName, $stream["filename"]);
    }

    /**
     * Test Invoice Detection is Done for Each Loaded Order
     *
     * Object Classes are Singletons: Invoice must never leak from one Order to another.
     *
     * @throws Exception
     *
     * @return void
     */
    public function testSuccessiveOrdersReading(): void
    {
        $this->loadLocalTestSequence("Monolangual");

        $invoicedId = $this->createOrder();
        $invoice = $this->createLinkedInvoice($invoicedId, true);
        $otherId = $this->createOrder();
        //====================================================================//
        // Read Invoiced Order, then Order without Invoice
        Assert::assertSame($invoice->ref, $this->readField($invoicedId, "invoiceNumber"));
        Assert::assertEmpty($this->readField($otherId, "invoiceNumber"));
        Assert::assertEmpty($this->readField($otherId, "invoicePdf"));
        //====================================================================//
        // Invoice Validated after a First Reading is Detected
        $lateInvoice = $this->createLinkedInvoice($otherId, true);
        Assert::assertSame($lateInvoice->ref, $this->readField($otherId, "invoiceNumber"));
        //====================================================================//
        // Back to First Order
        Assert::assertSame($invoice->ref, $this->readField($invoicedId, "invoiceNumber"));
    }

    /**
     * Delete the Generated Pdf of an Invoice, if any
     *
     * @param Facture $invoice
     *
     * @return void
     */
    private function removeInvoicePdf(Facture $invoice): void
    {
        $pdfPath = $this->getInvoiceFilesDir($invoice)
            .'/'.dol_sanitizeFileName((string) $invoice->ref).".pdf"
        ;
        if (is_file($pdfPath)) {
            unlink($pdfPath);
        }
        Assert::assertFileDoesNotExist($pdfPath);
    }

    /**
     * Read a Single Field of an Order
     *
     * @param string $orderId
     * @param string $fieldName
     *
     * @throws Exception
     *
     * @return mixed
     *
     * @phpstan-impure
     */
    private function readField(string $orderId, string $fieldName)
    {
        $data = Splash::object("Order")->get($orderId, array($fieldName));
        Assert::assertIsArray($data);

        return $data[$fieldName] ?? null;
    }

    /**
     * Create a Draft Order
     *
     * @throws Exception
     *
     * @return string
     */
    private function createOrder(): string
    {
        $fields = $this->fakeFieldsList("Order", array(), true);
        $fakeData = $this->fakeObjectData($fields);
        $fakeData["status"] = "OrderDraft";

        Splash::object("Order")->lock();
        $orderId = Splash::object("Order")->set(null, $fakeData);
        Assert::assertIsString($orderId);
        $this->addTestedObject("Order", $orderId);

        return $orderId;
    }

    /**
     * Create an Invoice & Link it to an Order
     *
     * @param string $orderId
     * @param bool   $validated
     *
     * @throws Exception
     *
     * @return Facture
     */
    private function createLinkedInvoice(string $orderId, bool $validated): Facture
    {
        global $db, $user;

        $fields = $this->fakeFieldsList("Invoice", array(), true);
        $fakeData = $this->fakeObjectData($fields);
        $fakeData["status"] = "PaymentDraft";

        Splash::object("Invoice")->lock();
        $invoiceId = Splash::object("Invoice")->set(null, $fakeData);
        Assert::assertIsString($invoiceId);
        $this->addTestedObject("Invoice", $invoiceId);
        //====================================================================//
        // Load Dolibarr Invoice
        $invoice = new Facture($db);
        Assert::assertGreaterThan(0, $invoice->fetch((int) $invoiceId));
        //====================================================================//
        // Link Invoice to Order: Order is the Source, Invoice the Target
        Assert::assertGreaterThan(0, $invoice->add_object_linked('commande', (int) $orderId));
        //====================================================================//
        // Validate Invoice if Required
        if ($validated) {
            if (Facture::STATUS_DRAFT == Invoice::getInvoiceStatusStatic($invoice)) {
                Assert::assertGreaterThan(0, $invoice->validate($user));
            }
            Assert::assertGreaterThan(0, $invoice->fetch((int) $invoiceId));
            Assert::assertNotSame(Facture::STATUS_DRAFT, Invoice::getInvoiceStatusStatic($invoice));
        }

        return $invoice;
    }

    /**
     * Get Dolibarr Documents Directory of an Invoice
     *
     * @param Facture $invoice
     *
     * @return string
     */
    private function getInvoiceFilesDir(Facture $invoice): string
    {
        global $conf;

        $entity = $invoice->entity ?: $conf->entity;
        $baseDir = $conf->facture->multidir_output[$entity] ?? ($conf->facture->dir_output ?? "");

        return $baseDir.'/'.dol_sanitizeFileName((string) $invoice->ref);
    }
}
