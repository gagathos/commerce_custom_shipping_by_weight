CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Calculation Rules
 * Requirements
 * Installation
 * Configuration
 * Instructions


INTRODUCTION
-------------
Commerce custom shipping by weight

This allows you to create a set of weights + shipping prices that would be applied to eligible shipments.  

For instance, you could use this module to set a flat rate for items that are less than 1lb.

Conditions are stored in a plugin "Shipment weight condition".  This uses EACH SHIPMENT to determine whether the method will show up for that shipment.

These conditions are used to track *Eligibility* for this shipping method to show.

CALCULATION RULES
-------------
*Calculation Rules* are used to calculate the actual shipping price, plus the base shipping rate.

This is defined in a comma separated list in the following format:

weight, unit, operator, price, currency
````
1,lb,<,10,USD
1.5,lb,<,10,USD
````
Would translate to something like this:

| weight | unit | operator | price | currency |
|--------|------|----------|-------|----------|
| 1      | lb   | <        | 10    | USD      |
| 1.5    | lb   | <        | 15    | USD      |

This is calculated PER SHIPMENT.

REQUIREMENTS
-------------

Drupal Commerce 2.x - https://www.drupal.org/project/commerce_shipping
Commerce Shipping 2.x - https://www.drupal.org/project/commerce_shipping
Physical fields - https://www.drupal.org/project/physical


INSTALLATION
------------

Install as you would normally install a contributed Drupal module. Visit
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
for further information.

This module can be used with Packer plugins which will split orders into multiple shipments.  


CONFIGURATION
-------------

The module configuration is similar to default commerce
shipping method configuration.


INSTRUCTIONS
-------------

To use this shipping method just try to follow next steps:
1. Create a new shipping method here:
   "/admin/commerce/config/shipping-methods/add"
2. Select "Custom Shipping By Weight" plugin.
3. Enter plugin label.
4. Set base rate amount for shipping method.
5. Open "Shipment" conditions list below.
6. Use the shipment condition to prevent this method for showing up for out-of-bounds shipment weight.
7. Enter calculation rules as instructed (more notes on format above)
8. Save shipping method configuration.
