# Issue reporting guidelines

To maintain an effective bugfix workflow and make sure issues will be solved in a timely manner we kindly ask reporters to follow some simple guidelines.

Before creating an issue, please do the following:

* Make sure the behavior you are reporting is really a bug, not a feature.
* Check the existing [issues](https://github.com/mailchimp/mc-magento2/issues) to make sure you are not duplicating somebody’s work.
* Make sure, that information you are about to report is a technical issue, please refer to the [Community Forums](http://ebizmarts.com/mailchimp-for-magento-support)  for technical questions.

If you are sure that the problem you are experiencing is caused by a bug, file a new issue in a Github issue tracker following the recommendations below.

## Title

Title is a vital part of bug report for developer and trigger to quickly identify a unique issue. A well written title should contain a clear, brief explanation of the issue, making emphasis on the most important points.

Good example would be:

> Unable to place order with Virtual product and PayPal.

Unclear example:

> Can't checkout.

## Issue Description

### Preconditions

Describing preconditions is a great start, provide information on system configuration settings you have changed, detailed information on entities created (Products, Customers, etc), Magento and mc-magento versions. Basically, everything that would help developer set up the same environment as you have.

Example:

    1. Magento CE 2.1.7 without sample data is installed.
    2. mc-magento2 1.0.21.
    3. Test category is set up.
    4. Virtual Product is created and assigned to the Test Category.
    ...

### Steps to reproduce

This part of the bug report is the most important, as developer will use this information to reproduce the issue. Problem is more likely to be fixed if it can be reproduced.

Precisely describe each step you have taken to reproduce the issue. Try to include as much information as possible, sometimes even minor differences can be crucial.

Example:

    1. Navigate to storefront as a guest.
    2. Open Test Category.
    3. Click "Add to Cart" on the Virtual Product.
    4. Open mini shopping cart and click "Proceed to Checkout".
    ...

### Actual and Expected result

To make sure that everybody involved in the fix are on the same page, precisely describe the result you expected to get and the result you actually observed after performing the steps.

Example:

    Expected result:
    Order is placed successfully, customer is redirected to the success page.
    Actual result:
    "Place order" button is not visible, order cannot be placed.

### Additional information

Additional information is often requested when the bug report is processed, you can save time by providing Magento and browser logs, screenshots, any other artifacts related to the issue at your own judgement.
