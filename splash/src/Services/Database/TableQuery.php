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

namespace Splash\Local\Services\Database;

use Splash\Client\Splash;
use Splash\Local\Local;

class TableQuery
{
    /**
     * Table to Use
     *
     * @var string
     */
    private string $table;

    /**
     * Label Fields
     *
     * @var string[]
     */
    private array $labels;

    /**
     * Key Fields
     */
    private ?string $key;

    /**
     * Parent Fields
     */
    private string $parent;

    /**
     * Where Clause
     *
     * @var string
     */
    private string $where;

    public function __construct(
        string $table,
        string $labels,
        ?string $key = null,
        ?string $parent = null,
        ?string $where = null
    ) {
        $this->table = $table;
        $this->labels = explode("|", $labels);
        $this->key = $key ?: null;
        $this->parent = $parent ?? "";
        $this->where = $where ?? "";
    }

    /**
     * Create a Table Query From Extra Fields Query String
     */
    public static function fromExtraFieldsQuery(string $queryString): ?self
    {
        $queryArray = explode(":", $queryString, 5);
        if (count($queryArray) < 3) {
            return null;
        }

        return new self(
            $queryArray[0],
            $queryArray[1],
            $queryArray[2],
            $queryArray[3] ?? null,
            $queryArray[4] ?? null
        );
    }

    /**
     * Get Values from Database.
     */
    public function getValues(?int $objectId = null, bool $translated = true): array
    {
        global $db, $langs;

        //====================================================================//
        // Build Sql Query
        $sql = sprintf("%s %s %s", self::getSelect(), self::getFrom(), self::getWhere($objectId));
        //====================================================================//
        // Execute Query
        if (!$reSql = $db->query($sql)) {
            return array();
        }
        //====================================================================//
        // Parse results
        $index = 0;
        $results = array();
        while ($index < $db->num_rows($reSql)) {
            $index++;
            $obj = $db->fetch_object($reSql);
            if (empty($obj->rowid ?? null)) {
                continue;
            }
            //====================================================================//
            // Build Value Name
            $names = array();
            foreach ($this->labels as $labelField) {
                $names[] = $translated
                    ? $langs->trans((string) $obj->{$labelField})
                    : (string) $obj->{$labelField}
                ;
            }
            $results[$obj->rowid] = implode(' ', $names);
        }

        return $results;
    }

    /**
     * Build Sql Select String
     */
    private function getSelect(): string
    {
        $keyList = empty($this->key) ? 'rowid' : $this->key.' as rowid';
        //====================================================================//
        // With join on ExtraField table
        if (!empty($this->where) && (false !== strpos($this->where, 'extra.'))) {
            $keyList = 'main.'.$this->key.' as rowid';
        }
        //====================================================================//
        // With join on Parent table
        if (!empty($this->parent) && ($parentField = explode('|', $this->parent)[0] ?? null)) {
            $keyList .= ', '.$parentField;
        }
        //====================================================================//
        // Add Labels to Select
        if (!empty($this->labels)) {
            $keyList .= ', '.implode(', ', $this->labels);
        }

        return "SELECT ".$keyList;
    }

    /**
     * Build Sql From Query Part
     */
    private function getFrom(): string
    {
        $from = 'FROM '.MAIN_DB_PREFIX.$this->table;
        //====================================================================//
        // We have to join on ExtraField table
        if (false !== strpos($this->where, 'extra')) {
            $from .= ' as main, '.MAIN_DB_PREFIX.$this->table.'_extrafields as extra';
        }

        return $from;
    }

    /**
     * Build SQL WHERE Clause
     */
    private function getWhere(?int $objectId = null): string
    {
        //====================================================================//
        // No Where Clause
        if (empty($where = $this->where)) {
            return 'WHERE 1=1';
        }
        //====================================================================//
        // Complete Where Clause
        $this->applyWhereReplacements($where, $objectId);
        //====================================================================//
        // Since Dolibarr V18.0.0 => Use Universal Formater
        $this->applyWhereUniversalFormat($where);
        //====================================================================//
        // We have to join on ExtraField table
        if ($this->key && false !== strpos($where, 'extra')) {
            return " WHERE extra.fk_object=main.".$this->key." AND ".$where;
        }

        return " WHERE ".$where;
    }

    /**
     * Apply Replacements to SQL WHERE Clause
     */
    private function applyWhereReplacements(string &$where, ?int $objectId = null): void
    {
        global $conf;

        //====================================================================//
        // Parse curent entity filter
        if (false !== strpos($where, '$ENTITY$')) {
            $where = str_replace('$ENTITY$', (string) $conf->entity, $where);
        }
        //====================================================================//
        // Can use SELECT request
        if (!getDolGlobalString("MAIN_DISALLOW_UNSECURED_SELECT_INTO_EXTRAFIELDS_FILTER")) {
            if (false !== strpos($where, '$SEL$')) {
                $where = str_replace('$SEL$', 'SELECT', $where);
            }
        }
        //====================================================================//
        // Parse current object id can be used into filter
        if (false !== strpos($where, '$ID$')) {
            $where = str_replace('$ID$', (string) ($objectId ?? "0"), $where);
        }
    }

    /**
     * Apply Universal Format to SQL WHERE Clause
     */
    private function applyWhereUniversalFormat(string &$where): void
    {
        //====================================================================//
        // Since Dolibarr V18.0.0
        if (Local::dolVersionCmp("18.0.0") < 0) {
            return;
        }
        //====================================================================//
        // Safety Check
        assert(function_exists("forgeSQLFromUniversalSearchCriteria"));
        //====================================================================//
        // Apply regex on Clause
        $reg = array();
        if (preg_match('/^\(?([a-z0-9]+)([=<>]+)(\d+)\)?$/i', $where, $reg)) {
            $where = '('.$reg[1].':'.$reg[2].':'.$reg[3].')';
        }
        //====================================================================//
        // Apply Universal Formating
        /** @phpstan-var null|string $errStr */
        $errStr = null;
        $where = forgeSQLFromUniversalSearchCriteria($where, $errStr, 1);
        if ($errStr) {
            Splash::log()->err($errStr);
        }
    }
}
