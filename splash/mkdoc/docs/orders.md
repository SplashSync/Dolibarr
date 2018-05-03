
### Configure Import Features 

Since v1.4 of Splash Module for Dolibarr, a dedicated configuration block group all Orders & Invoice imports parameters. 

![](https://splashsync.github.io/Dolibarr/img/screenshot_6.png)

### Tax Rates Detection (NEW) 

When importing orders & Invoices lines, Splash Module is now able to identify line's tax rate using a shared Tax Code. 

This feature is usefull for countries that have multiple or complex VAT rates (i.e. Canada).

**How to configure it ?**
  
First, you need to create define, on each servers, the same Codes for VAT Rates. For Dolibarr, this setup is  available in **Settings >> Dictionary >> VAT Rates or Sales Tax Rates**  

With Dolibarr, VAT Rate name is "Code", this value is empty by default. Generaly, you can use codes used by your E-Commerce.  

![](https://splashsync.github.io/Dolibarr/img/screenshot_8.png)

**How it works ?**

If you have a look at the data that are now available for Orders & Invoices objects, you will see a new field called "VAT Rate". 

![](https://splashsync.github.io/Dolibarr/img/screenshot_9.png)

When Splash import an Order or an Invoice, if the given code if found on your Dolibarr Dictionnary, Splash will setup this VAT Rate for creating this product line. 

**Limitations**

Up to now, only part of our modules are compatible with this feature.

To use this feature, you must ensure VAT Rates Codes are **strictly** similar on all connected applications.


### Import of Guests Orders (NEW)

**Why ?**

Most parts of moderns E-Commerce platforms now offer to customers the possibility to place an order without creating any customer account.
On the ERP side, it is not possible to create an Order (or Invoice) without pointing to a customer. 
To solve this problem, we developped a specific feature. 

**What is does ?**

When you enable **Import of Guests Orders & Invoices**, Splash will remove the **requiered** flag for customers link. This way, Splash Server will push all new Orders & Invoices to Dolibarr.
Whatever if they have a customer defined or not.

In this mode, any Order (or Invoice) that has no customer defined will be attached to a predefined default customer.

**Configuration**

To Use this Mode, just enable the feature and select the default customer to use. We highly recommand creation of a dedicated customer.

**Email detection**

This additionnal feature may be used to detect already known customers using their Email if provided by the Server. 
If the give Email belong to an existing ThirdParty, the order will be attached to this customer and not to default customer. 
 

