CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Instructions


INTRODUCTION
-------------
Commerce shipping weight vsawyer

Allows the creation of complex matrices of shipping vsawyers by order weight,
only for "Shipping by weight vsawyer" shipping method.
For example:
                   < .5kg | < 2kg | < 10kg
Base rate: £2  +   |   £2   |  £4   |  £6
Calculated rate:   |   £4   |  £6   |  £8

Conditions are stored in a plugin "Shipment weight condition".  This uses EACH SHIPMENT to determine whether the method will show up for that shipment.

These conditions are used to track *Eligibility* for this shipping method to show.

*Calculation Rules* are used to calculate the actual shipping price, plus the base shipping rate.

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
6. Use the shipment condition to prevent this method for showing up for out-of-bounds shipping.
7. Configure the price matrix according to the instructions on the page	1
8. Save shipping method configuration.


