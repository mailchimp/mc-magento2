
<h3>Labels applied by the team</h3>

| Label        | Description           |
| ------------- |-------------|
| ![bug](https://s3.amazonaws.com/ebizmartsgithubimages/bug.png) | Bug report contains sufficient information to reproduce. Will be solved for associated Milestone.|
| ![enhancement](https://s3.amazonaws.com/ebizmartsgithubimages/enhancement.png) | Improvement accepted. Will be added for associated Milestone.|
| ![done](https://s3.amazonaws.com/ebizmartsgithubimages/done.png) | Issue has been solved and will be applied in the associated Milestone. |
| ![duplicate](https://s3.amazonaws.com/ebizmartsgithubimages/duplicate.png) | Issue has been already reported and will be closed with no further action. |
| ![wrong issue format](https://s3.amazonaws.com/ebizmartsgithubimages/wrongissueformat.png) | Issue has not been created according to requirements at the [Issue reporting guidelines](https://github.com/mailchimp/mc-magento2/wiki/Issue-reporting-guidelines). Will be closed until requirements are met. |
| ![feature request](https://s3.amazonaws.com/ebizmartsgithubimages/featurerequest.png) | Feature request to be considered by the team. After approval will be labeled as enhancement. |
| ![could not replicate](https://s3.amazonaws.com/ebizmartsgithubimages/couldnotreplicate.png) | The team was not able to replicate issue. It will be closed until missing information is given. |
| ![contact support](https://s3.amazonaws.com/ebizmartsgithubimages/contactsupport.png) | Contact our support team at mailchimp@ebizmarts-desk.zendesk.com. Issue will be closed with no further action. |
| ![low priority](https://s3.amazonaws.com/ebizmartsgithubimages/lowpriority.png) | Issue is considered as low priority by the team. |
| ![priority](https://s3.amazonaws.com/ebizmartsgithubimages/priority.png) | Issue is considered as high priority by the team. |
| ![conflict](https://s3.amazonaws.com/ebizmartsgithubimages/conflict.png) | Issue reports a conflict with other third party extension. |
| ![need feedback](https://s3.amazonaws.com/ebizmartsgithubimages/needfeedback.png) | Feedback is required to continue working on the issue. If there is no answer after a week it will be closed. |
| ![blocked](https://s3.amazonaws.com/ebizmartsgithubimages/blocked.png) | Issue can not be solved due to external causes. |
| ![read documentation](https://s3.amazonaws.com/ebizmartsgithubimages/readdocumentation.png) | Issue will be closed. Available documentation: [MailChimp For Magento doc](https://kb.mailchimp.com/integrations/e-commerce/connect-or-disconnect-mailchimp-for-magento-2)|




# Issue reporting guidelines

To maintain an effective bugfix workflow and make sure issues will be solved in a timely manner we kindly ask reporters to follow some simple guidelines.

Before creating an issue, please do the following:

* Check the [documentation](https://kb.mailchimp.com/integrations/e-commerce/connect-or-disconnect-mailchimp-for-magento-2) to make sure the behavior you are reporting is really a bug, not a feature.
* Check the existing [issues](https://github.com/mailchimp/mc-magento2/issues) to make sure you are not duplicating somebody’s work.
* Make sure, that information you are about to report is a technical issue, please refer to the [Community Forums](http://ebizmarts.com/mailchimp-for-magento-support)  for technical questions.

If you are sure that the problem you are experiencing is caused by a bug, file a new issue in a Github issue tracker following the recommendations below.

## Title

Title is a vital part of bug report for developer and triager to quickly identify a unique issue. A well written title should contain a clear, brief explanation of the issue, making emphasis on the most important points.

Good example would be:

> Unable to place order with Virtual product and PayPal.

Unclear example:

> Can't checkout.

## Issue Description

### Preconditions

Describing preconditions is a great start, provide information on system configuration settings you have changed, detailed information on entities created (Products, Customers, etc), Magento and mc-magento versions. Basically, everything that would help developer set up the same environment as you have.

Example:

    1. Magento CE 2.0.1 without sample data is installed.
    2. mc-magento 1.0.6.
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

## Pull requests

Before creating a pull request please make sure to follow this [guidelines](https://github.com/mailchimp/mc-magento2/wiki/Pull-Request-guideliness) or it will be rejected.
