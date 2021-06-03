# Change Log

## [103.4.43](https://github.com/mailchimp/mc-magento2/tree/103.4.43)

[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/102.3.42...103.4.43)

**Implemented enhancements:**

- Add a button in the admin to resync all products [\#1184](https://github.com/mailchimp/mc-magento2/issues/1184)
- Missing indexes on mailchimp\_errors [\#1162](https://github.com/mailchimp/mc-magento2/issues/1162)
- Ignore modified items when flagging store as synced [\#1140](https://github.com/mailchimp/mc-magento2/issues/1140)
- Only fetch specific columns from sales\_order [\#1134](https://github.com/mailchimp/mc-magento2/issues/1134)
- Error table never getting cleaned up [\#1107](https://github.com/mailchimp/mc-magento2/issues/1107)

**Fixed bugs:**

- Change the low value for date sync to a valid one [\#1192](https://github.com/mailchimp/mc-magento2/issues/1192)
- Exclude the bundle and grouped products for the product collection [\#1191](https://github.com/mailchimp/mc-magento2/issues/1191)
- The product image url don't contain the secure url if Use Secure URLs on Storefront is ON [\#1179](https://github.com/mailchimp/mc-magento2/issues/1179)
- Mark products as modified when use import products from the admin [\#1167](https://github.com/mailchimp/mc-magento2/issues/1167)
- Issue with "Magento Always Manage Emails" when Unsubscribing from a Customer Account [\#1157](https://github.com/mailchimp/mc-magento2/issues/1157)
- errors in cron related to ebizmarts\_webhooks [\#1152](https://github.com/mailchimp/mc-magento2/issues/1152)
- Ecommerce order send loop [\#1112](https://github.com/mailchimp/mc-magento2/issues/1112)
- Problem with suscription [\#1106](https://github.com/mailchimp/mc-magento2/issues/1106)
- Allow more than 10 interest inside a group [\#1103](https://github.com/mailchimp/mc-magento2/issues/1103)
- observer name that breaks Magento 2 DOM XML [\#1102](https://github.com/mailchimp/mc-magento2/issues/1102)
- CSP Whitelist Support [\#1097](https://github.com/mailchimp/mc-magento2/issues/1097)
- Infinite loop on customer account creation if email present in newsletter subscribers list [\#1090](https://github.com/mailchimp/mc-magento2/issues/1090)
- The confirmation email is sent twice because the getImportMode \(\) method cannot be honored [\#1089](https://github.com/mailchimp/mc-magento2/issues/1089)
- Ecommerce Cron error "Requested country is not available." [\#1084](https://github.com/mailchimp/mc-magento2/issues/1084)
- Subscribing for a second time does not work. [\#1078](https://github.com/mailchimp/mc-magento2/issues/1078)

## [102.3.42](https://github.com/mailchimp/mc-magento2/tree/102.3.42)

[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/102.3.41...102.3.42)

**Implemented enhancements:**

- Create custom field mapping [\#1025](https://github.com/mailchimp/mc-magento2/issues/1025)

**Fixed bugs:**

- Subscribing for a second time does not work. [\#1078](https://github.com/mailchimp/mc-magento2/issues/1078)
- Invalid date format when use mysql 8 [\#1066](https://github.com/mailchimp/mc-magento2/issues/1066)
- Unable to capture the order for a campaign [\#1065](https://github.com/mailchimp/mc-magento2/issues/1065)
- Notice: Undefined index: image\_url Model/Api/Product.php [\#1059](https://github.com/mailchimp/mc-magento2/issues/1059)
- Multistore product sync wrong name  [\#1055](https://github.com/mailchimp/mc-magento2/issues/1055)

## [102.3.41](https://github.com/mailchimp/mc-magento2/tree/102.3.41)

[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/102.3.40...102.3.41)

**Implemented enhancements:**

- Coupon Codes \(Promo Codes\) Do Not Send to Mailchimp With Order Data [\#1032](https://github.com/mailchimp/mc-magento2/issues/1032)

**Fixed bugs:**

- "Unable to unserialize value." issue avoids ecommerce syncing. This happens some times when "Send Promo Codes and Promo Rules" is enabled [\#1035](https://github.com/mailchimp/mc-magento2/issues/1035)
- Fallback to JQueryUI Compat activated. [\#1034](https://github.com/mailchimp/mc-magento2/issues/1034)
- Abandoned Cart revenue not showing on Mailchimp account [\#1033](https://github.com/mailchimp/mc-magento2/issues/1033)

## [102.3.40](https://github.com/mailchimp/mc-magento2/tree/102.3.40)

[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/102.3.39...102.3.40)

**Implemented enhancements:**

- Add magento 2.4 compatibility [\#1027](https://github.com/mailchimp/mc-magento2/issues/1027)

## [102.3.39](https://github.com/mailchimp/mc-magento2/tree/102.3.39)

[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/102.3.38...102.3.39)

**Implemented enhancements:**

- Licensing? [\#996](https://github.com/mailchimp/mc-magento2/issues/996)

**Fixed bugs:**

- Typo in campaigncatcher.js causing Javascript error [\#1015](https://github.com/mailchimp/mc-magento2/issues/1015)
- The mailchimp groups are not shown correctly in the customer account [\#1009](https://github.com/mailchimp/mc-magento2/issues/1009)
- Improper call to interest-categories in the webhook processing [\#1002](https://github.com/mailchimp/mc-magento2/issues/1002)
- MapFields not getting synced when configured in storeView. [\#998](https://github.com/mailchimp/mc-magento2/issues/998)
- Eternal Spinning gif after invalid API key has been entered in the configuration [\#990](https://github.com/mailchimp/mc-magento2/issues/990)
- Syncing customer group changed some customers group id to 0 [\#989](https://github.com/mailchimp/mc-magento2/issues/989)
- Issue syncing DOB merge field [\#987](https://github.com/mailchimp/mc-magento2/issues/987)
- JS error on product page with slow internet [\#912](https://github.com/mailchimp/mc-magento2/issues/912)

## [102.3.38](https://github.com/mailchimp/mc-magento2/tree/102.3.38)

[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/102.3.37...102.3.38)

**Implemented enhancements:**

- Optimize MailChimp JS block [\#895](https://github.com/mailchimp/mc-magento2/issues/895)
- Optimize MailChimp JS block [\#891](https://github.com/mailchimp/mc-magento2/pull/891) ([andrey-legayev](https://github.com/andrey-legayev))

**Fixed bugs:**

- Incorrect import in Webhook controller [\#976](https://github.com/mailchimp/mc-magento2/issues/976)
- The street line 3 is not synced in orders [\#963](https://github.com/mailchimp/mc-magento2/issues/963)
- Loading screen stuck when attempting to save the API key. [\#940](https://github.com/mailchimp/mc-magento2/issues/940)
- \[Performance Issue\] HTTP calls to mailchimp on every page request and config cache flush [\#939](https://github.com/mailchimp/mc-magento2/issues/939)
- Strong check for interest groups [\#932](https://github.com/mailchimp/mc-magento2/issues/932)
- Take the first date value from storeview scope. [\#931](https://github.com/mailchimp/mc-magento2/issues/931)
- Bad management of the groups in the webhooks [\#926](https://github.com/mailchimp/mc-magento2/issues/926)
- Error grid, bad data when try to sort for one field 3 times [\#922](https://github.com/mailchimp/mc-magento2/issues/922)
- Uncaught TypeError: strpos\(\) expects parameter 1 to be string, null given in vendor/magento/module-theme/Controller/Result/JsFooterPlugin.php:44 in Magento 2.3.3 [\#920](https://github.com/mailchimp/mc-magento2/issues/920)
- JS error on product page with slow internet [\#912](https://github.com/mailchimp/mc-magento2/issues/912)
- Invalid API key error when attempting to update settings. [\#906](https://github.com/mailchimp/mc-magento2/issues/906)
- Typo in Ecommerce cron when mark an object with error [\#900](https://github.com/mailchimp/mc-magento2/issues/900)
- Error in cron when split databases [\#887](https://github.com/mailchimp/mc-magento2/issues/887)
- Make changes to pass code sniffer [\#881](https://github.com/mailchimp/mc-magento2/issues/881)
- Fix catching campaign with enabled Varnish FPC [\#874](https://github.com/mailchimp/mc-magento2/issues/874)
- Fix incorrect import in Webhook controller [\#973](https://github.com/mailchimp/mc-magento2/pull/973) ([ihor-sviziev](https://github.com/ihor-sviziev))

## [102.3.37](https://github.com/mailchimp/mc-magento2/tree/102.3.37)

[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/102.3.36...102.3.37)

**Implemented enhancements:**

- Make strong check for the API key [\#849](https://github.com/mailchimp/mc-magento2/issues/849)
- Add alt text to the order grid in the mailchimp sync image [\#810](https://github.com/mailchimp/mc-magento2/issues/810)
- Send the merge\_fields for customers [\#790](https://github.com/mailchimp/mc-magento2/issues/790)

**Fixed bugs:**

- Do not add anything to frontend if module disabled [\#866](https://github.com/mailchimp/mc-magento2/issues/866)
- Made sure a missing "simple\_sku" won't break the entire sync [\#862](https://github.com/mailchimp/mc-magento2/issues/862)
- Not all batches are saved when multi store enabled [\#857](https://github.com/mailchimp/mc-magento2/issues/857)
- Mark orders with error to not try to re-sync [\#841](https://github.com/mailchimp/mc-magento2/issues/841)
- PayPal Express orders sync fails - Last name is NULL [\#840](https://github.com/mailchimp/mc-magento2/issues/840)
- Bad management of old batches [\#821](https://github.com/mailchimp/mc-magento2/issues/821)
- Bad batches management [\#817](https://github.com/mailchimp/mc-magento2/issues/817)
- Bad way to test if the json\_encode fails [\#805](https://github.com/mailchimp/mc-magento2/issues/805)
- Rename delete customer account option [\#801](https://github.com/mailchimp/mc-magento2/issues/801)
- Orders with no products are not marked as synced [\#797](https://github.com/mailchimp/mc-magento2/issues/797)
- Re sync the subscriber when the customer is modified [\#786](https://github.com/mailchimp/mc-magento2/issues/786)
- Use always the md5 of the customer email to identify the customer [\#782](https://github.com/mailchimp/mc-magento2/issues/782)
- Error when select a website scope in config [\#773](https://github.com/mailchimp/mc-magento2/issues/773)
- Change the Resync Customers button [\#768](https://github.com/mailchimp/mc-magento2/issues/768)
-  ebizmarts\_ecommerce has an error [\#767](https://github.com/mailchimp/mc-magento2/issues/767)
- Unhandled `Magento\Framework\Serialize\Serializer\Json::\(un\)serialize` calls [\#758](https://github.com/mailchimp/mc-magento2/issues/758)

## [102.3.36](https://github.com/mailchimp/mc-magento2/tree/102.3.36)

[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/102.3.35...102.3.36)

**Implemented enhancements:**

- Take the version from the composer.json [\#759](https://github.com/mailchimp/mc-magento2/issues/759)

## [102.3.35](https://github.com/mailchimp/mc-magento2/tree/102.3.35)

[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/102.3.34...101.2.35)

**Implemented enhancements:**

- Show the amount of list subscribers [\#740](https://github.com/mailchimp/mc-magento2/issues/740)
- Wrong error message in order grid [\#710](https://github.com/mailchimp/mc-magento2/issues/710)
- Add a button to re sync customers [\#702](https://github.com/mailchimp/mc-magento2/issues/702)
- Encrypt sensitive data [\#701](https://github.com/mailchimp/mc-magento2/issues/701)
- Some exceptions are not added into logs, making it hard to find the error message. [\#700](https://github.com/mailchimp/mc-magento2/issues/700)

**Fixed bugs:**

- Remove error message when resend an item with error [\#719](https://github.com/mailchimp/mc-magento2/issues/719)
- Error message 'Resource Not Found' on creating new customers [\#715](https://github.com/mailchimp/mc-magento2/issues/715)
- Don't delete the cart from ecommerce table when the order is made [\#706](https://github.com/mailchimp/mc-magento2/issues/706)
- Success unsubscription, if subscribe again doesn't send confirm subscription emails [\#696](https://github.com/mailchimp/mc-magento2/issues/696)
- Customer and subscriber with same email sent to mailchimp with different id [\#692](https://github.com/mailchimp/mc-magento2/issues/692)
- Order is not marked to resync when the credit memo comes via magento API [\#687](https://github.com/mailchimp/mc-magento2/issues/687)
- Order is not marked to resync when the invoice comes via magento API [\#682](https://github.com/mailchimp/mc-magento2/issues/682)
- Order is not marked to resync when the shipment comes via magento API [\#678](https://github.com/mailchimp/mc-magento2/issues/678)
- Multistore with different Mailchimp account wrong synchronization on customer delete/unsubcribe from magento admin [\#674](https://github.com/mailchimp/mc-magento2/issues/674)
- MySQL error during setup:upgrade after module install \(with split database Magento EE feature enabled\) [\#664](https://github.com/mailchimp/mc-magento2/issues/664)

## [102.3.34](https://github.com/mailchimp/mc-magento2/tree/102.3.34)

[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.3.33...102.3.34)

**Implemented enhancements:**

- Add link to Terms of service [\#641](https://github.com/mailchimp/mc-magento2/issues/641)
- Delete the Cart register in the sync\_commerce table when confirm the order [\#626](https://github.com/mailchimp/mc-magento2/issues/626)
- Add some other logs [\#602](https://github.com/mailchimp/mc-magento2/issues/602)
- Clean the maichimp\_sync\_batches table [\#598](https://github.com/mailchimp/mc-magento2/issues/598)
- Avoid getByEmail calls when sending Orders and Carts to Mailchimp [\#468](https://github.com/mailchimp/mc-magento2/issues/468)
- Put a column in the order grid to show if the order was synced [\#140](https://github.com/mailchimp/mc-magento2/issues/140)

**Fixed bugs:**

- Change the version numeration to meet the magento marketplace requirements [\#649](https://github.com/mailchimp/mc-magento2/issues/649)
- Save the email in the quote only with the agreement from the customer [\#645](https://github.com/mailchimp/mc-magento2/issues/645)
- Not send the carts until the store is completely synced [\#636](https://github.com/mailchimp/mc-magento2/issues/636)
- Defer the load of the mailchimp js [\#630](https://github.com/mailchimp/mc-magento2/issues/630)
- No first and lastname when the order is from a guest [\#609](https://github.com/mailchimp/mc-magento2/issues/609)
- Subscriber not sent if country state is empty. [\#593](https://github.com/mailchimp/mc-magento2/issues/593)
- Ecommerce cronjob stuck on customers with no address \(error occurs\) [\#400](https://github.com/mailchimp/mc-magento2/issues/400)


## [1.3.33](https://github.com/mailchimp/mc-magento2/tree/1.3.33)

[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.0.32...1.3.33)

**Implemented enhancements:**

- Change all mentions from MailChimp to Mailchimp and change the menu icon to the new one [\#565](https://github.com/mailchimp/mc-magento2/issues/565)
- Add possibility to send the product's price including taxes [\#532](https://github.com/mailchimp/mc-magento2/issues/532)
- Sync performance in large stores [\#502](https://github.com/mailchimp/mc-magento2/issues/502)
- Clean the table mailchimp\_webhook\_request [\#486](https://github.com/mailchimp/mc-magento2/issues/486)
- Add option to not send Promo Codes and Promo Rules [\#481](https://github.com/mailchimp/mc-magento2/issues/481)
- Ask for confirmation when removing mailchimp store [\#480](https://github.com/mailchimp/mc-magento2/issues/480)
- add magento 2.3 compatibility [\#494](https://github.com/mailchimp/mc-magento2/pull/494) ([gonzaloebiz](https://github.com/gonzaloebiz))

**Fixed bugs:**

- Spelling error in order status sent to mailchimp [\#574](https://github.com/mailchimp/mc-magento2/issues/574)
- Error during sync: "A campaign with the provided ID does not exist in the account for this list." [\#561](https://github.com/mailchimp/mc-magento2/issues/561)
- No campaign assigned to orders [\#554](https://github.com/mailchimp/mc-magento2/issues/554)
- Missing Customer Fields Mapping [\#553](https://github.com/mailchimp/mc-magento2/issues/553)
- Customers generate entries in the mailchimp\_sycn\_ecommerce with related\_id = null [\#541](https://github.com/mailchimp/mc-magento2/issues/541)
- Re sync the parent product when modifiy a child one [\#537](https://github.com/mailchimp/mc-magento2/issues/537)
- Could not resolve host: xx.api.mailchimp.com [\#523](https://github.com/mailchimp/mc-magento2/issues/523)
- Incorrect price in configurable product [\#513](https://github.com/mailchimp/mc-magento2/issues/513)
- No timezone saved when create a new Mailchimp store [\#512](https://github.com/mailchimp/mc-magento2/issues/512)
- Cart not updated in a Abandoned Cart Series [\#498](https://github.com/mailchimp/mc-magento2/issues/498)
- "Unable to unserialize value " when run the ecommerce cron process [\#473](https://github.com/mailchimp/mc-magento2/issues/473)
- multi-site Customer Fields Mapping not syncing correctly [\#471](https://github.com/mailchimp/mc-magento2/issues/471)
- Previously unsubscribed guest/customer is not resubscribed when selecting groups on success page [\#365](https://github.com/mailchimp/mc-magento2/issues/365)

## [1.0.31](https://github.com/mailchimp/mc-magento2/tree/1.0.31)

[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.0.30...1.0.31)

**Implemented enhancements:**

- Add a combo in the Customer Fields Mapping with the MailChimp defined tags [\#423](https://github.com/mailchimp/mc-magento2/issues/423)

**Fixed bugs:**

- Error of serialization when processing webhook data [\#455](https://github.com/mailchimp/mc-magento2/issues/455)
- Don't use serialize function directly [\#451](https://github.com/mailchimp/mc-magento2/issues/451)
- Error when a product has SKU = null [\#448](https://github.com/mailchimp/mc-magento2/issues/448)
- Send the product id in the order when is a configurable [\#445](https://github.com/mailchimp/mc-magento2/issues/445)
- main.CRITICAL: API Key Missing for Api Call: https://usxx.api.mailchimp.com/3.0/lists//merge-fields - Your request did not include an API key. [\#442](https://github.com/mailchimp/mc-magento2/issues/442)
- Wrong parent id in the cart [\#432](https://github.com/mailchimp/mc-magento2/issues/432)
- Abandoned cart email queue in Mailchimp dashboard not getting reset on placing the order [\#431](https://github.com/mailchimp/mc-magento2/issues/431)
- Collissions with mailchimp cookies [\#429](https://github.com/mailchimp/mc-magento2/issues/429)
- Subscriber which was added in MailChimp doesn't have storeId in Magento2 [\#427](https://github.com/mailchimp/mc-magento2/issues/427)
- Product Image does not show on abandoned cart email template / order details  [\#425](https://github.com/mailchimp/mc-magento2/issues/425)
- Total orders\_count and total\_spent sent incorrectly in order [\#420](https://github.com/mailchimp/mc-magento2/issues/420)
- NULL price when special price is not set, but special price dates are set  [\#416](https://github.com/mailchimp/mc-magento2/issues/416)
- field \[operations.item:2\] : Schema describes object, array found instead [\#409](https://github.com/mailchimp/mc-magento2/issues/409)
- Empty data in the batch json for custom products types [\#406](https://github.com/mailchimp/mc-magento2/issues/406)
- After syncing data with MailChimp the wrong products are show for my stores [\#404](https://github.com/mailchimp/mc-magento2/issues/404)
- Fix unique type validation in di.xml [\#450](https://github.com/mailchimp/mc-magento2/pull/450) ([ihor-sviziev](https://github.com/ihor-sviziev))

## [1.0.30](https://github.com/mailchimp/mc-magento2/tree/1.0.30) (2018-09-18)
[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.0.29...1.0.30)

**Implemented enhancements:**

- Adding extra logging on import parts of the Cronjob [\#393](https://github.com/mailchimp/mc-magento2/issues/393)
- Add an option to choose between send MailChimp or Magento mails [\#372](https://github.com/mailchimp/mc-magento2/issues/372)
- Log batch totals for each run [\#361](https://github.com/mailchimp/mc-magento2/issues/361)
-  Add debug information [\#359](https://github.com/mailchimp/mc-magento2/issues/359)
- High CPU load when API not available [\#325](https://github.com/mailchimp/mc-magento2/issues/325)

**Fixed bugs:**

- MailChimp breaks order processing when it's done through CLI [\#378](https://github.com/mailchimp/mc-magento2/issues/378)
- Disabled in Admin Panel Mailchimp block Magento default newsletter flow. [\#339](https://github.com/mailchimp/mc-magento2/issues/339)
- Special prices management in Magento Enterprise [\#391](https://github.com/mailchimp/mc-magento2/issues/391)
- Failed to open stream: No such file or directory [\#388](https://github.com/mailchimp/mc-magento2/issues/388)
- Promo rules are not updated [\#370](https://github.com/mailchimp/mc-magento2/issues/370)
- Error in the configuration when selecting other website than default [\#368](https://github.com/mailchimp/mc-magento2/issues/368)
- Orders not synced when products in the order not already synced [\#366](https://github.com/mailchimp/mc-magento2/issues/366)
- Wrong website set on customer [\#357](https://github.com/mailchimp/mc-magento2/issues/357)
- Issue with coupons for free shipping [\#355](https://github.com/mailchimp/mc-magento2/issues/355)
- Error downloading response from error grid when batch not exist [\#351](https://github.com/mailchimp/mc-magento2/issues/351)
- Unable to set custom Env.php API Credentials  [\#345](https://github.com/mailchimp/mc-magento2/issues/345)
- Webhook processing fails when list id does not match any list configured in Magento [\#337](https://github.com/mailchimp/mc-magento2/issues/337)
- Sending modified products in order or cart [\#335](https://github.com/mailchimp/mc-magento2/issues/335)
- Base table or view not found Magento 2.2.4 [\#321](https://github.com/mailchimp/mc-magento2/issues/321)
- Unknown column 'at\_special\_from\_date\_default.value' in 'on clause' [\#309](https://github.com/mailchimp/mc-magento2/issues/309)

## [1.0.29](https://github.com/mailchimp/mc-magento2/tree/1.0.29) (2018-05-31)
[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.0.28...1.0.29)

**Implemented enhancements:**

- Add check before renaming 'address\_address1' table column [\#283](https://github.com/mailchimp/mc-magento2/pull/283) ([rob-aimes](https://github.com/rob-aimes))

**Fixed bugs:**

- No cron\_groups.xml is defined [\#316](https://github.com/mailchimp/mc-magento2/issues/316)
- Wrong url for a generic product of configurable producs [\#313](https://github.com/mailchimp/mc-magento2/issues/313)
- No image url when the product and the parent has no image [\#307](https://github.com/mailchimp/mc-magento2/issues/307)
- web\_hooks error somewhere - first/last name being required [\#302](https://github.com/mailchimp/mc-magento2/issues/302)
- In the mailchimpstore grid not all the apikeys are taken [\#295](https://github.com/mailchimp/mc-magento2/issues/295)
- Error getting interest groups [\#293](https://github.com/mailchimp/mc-magento2/issues/293)
- Multistore with different Mailchimp accounts not saving correctly [\#289](https://github.com/mailchimp/mc-magento2/issues/289)
- Exception is thrown when Promotion is marked for removal [\#280](https://github.com/mailchimp/mc-magento2/issues/280)
- Webhook cronjob fails when updating customer [\#278](https://github.com/mailchimp/mc-magento2/issues/278)
- Cron Ecommerce: cannot create batches data because update existed products in orders or carts [\#277](https://github.com/mailchimp/mc-magento2/issues/277)
- Cancelled or pending orders added to revenue in mailchimp [\#274](https://github.com/mailchimp/mc-magento2/issues/274)
- Bad registers are generated in mailchimp\_sync\_ecommerce table [\#267](https://github.com/mailchimp/mc-magento2/issues/267)
- PHP warning in Helper/Data.php line 340 [\#266](https://github.com/mailchimp/mc-magento2/issues/266)
- Subscriber fields not updated when Ecommerce Data not enabled [\#258](https://github.com/mailchimp/mc-magento2/issues/258)
- The instest groups are not reloaded when the list changes [\#257](https://github.com/mailchimp/mc-magento2/issues/257)
- The user can select non existing group in admin [\#256](https://github.com/mailchimp/mc-magento2/issues/256)
- Don't process stores with no mailchimp store [\#255](https://github.com/mailchimp/mc-magento2/issues/255)
- Error when attempting to edit a customer from the backend [\#240](https://github.com/mailchimp/mc-magento2/issues/240)
- 2.2 Error Importing Configuration [\#223](https://github.com/mailchimp/mc-magento2/issues/223)
- No Abandoned Cart Data Sent [\#220](https://github.com/mailchimp/mc-magento2/issues/220)
- Magento 2.2.1: Changes like "unsubscribe" and "delete" to subscribers in Magento backend aren't synched [\#147](https://github.com/mailchimp/mc-magento2/issues/147)
- Change cron group id to 'mailchimp'. [\#282](https://github.com/mailchimp/mc-magento2/pull/282) ([jhruehl](https://github.com/jhruehl))
- fix decodeArrayFieldValue error [\#265](https://github.com/mailchimp/mc-magento2/pull/265) ([gundamkid](https://github.com/gundamkid))

## [1.0.28](https://github.com/mailchimp/mc-magento2/tree/1.0.28) (2018-03-27)
[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.0.27...1.0.28)

**Implemented enhancements:**

- Not necessary mailchimp/script/get requests [\#248](https://github.com/mailchimp/mc-magento2/issues/248)
- Add a button to create the webhooks [\#229](https://github.com/mailchimp/mc-magento2/issues/229)
- Add get Api credentials button using oauth. [\#207](https://github.com/mailchimp/mc-magento2/issues/207)
- Special price management [\#194](https://github.com/mailchimp/mc-magento2/issues/194)
- Use a checkbox on Checkout to determine Opt-in status. [\#36](https://github.com/mailchimp/mc-magento2/issues/36)

**Fixed bugs:**

- Wrong product marked as modified in ecommerce table [\#253](https://github.com/mailchimp/mc-magento2/issues/253)
- Don't delete the batch\_id when modify a register [\#246](https://github.com/mailchimp/mc-magento2/issues/246)
- Invalid argument supplied for foreach\(\) [\#243](https://github.com/mailchimp/mc-magento2/issues/243)
- Missing argument on call to \_updateSyncData\(\) [\#241](https://github.com/mailchimp/mc-magento2/issues/241)
- MailChimp js file loaded each time the page loads [\#232](https://github.com/mailchimp/mc-magento2/issues/232)
- Mark non existing batchs as canceled [\#216](https://github.com/mailchimp/mc-magento2/issues/216)
- Modified carts are not re synced [\#212](https://github.com/mailchimp/mc-magento2/issues/212)
- Try to get result for not existing batch [\#210](https://github.com/mailchimp/mc-magento2/issues/210)
- Wrong error management [\#204](https://github.com/mailchimp/mc-magento2/issues/204)
- When modify a simple product, the variant is empty [\#202](https://github.com/mailchimp/mc-magento2/issues/202)
- Abandoned cart email product price has range starting at $0 when using configurable products [\#197](https://github.com/mailchimp/mc-magento2/issues/197)
- Error due to customer data race condition [\#112](https://github.com/mailchimp/mc-magento2/issues/112)
- Update default.xml [\#180](https://github.com/mailchimp/mc-magento2/pull/180) ([jhruehl](https://github.com/jhruehl))
- Show "Mailchimp" customer tab when the extension is enabled [\#201](https://github.com/mailchimp/mc-magento2/pull/201) ([t-richards](https://github.com/t-richards))

## [1.0.27](https://github.com/mailchimp/mc-magento2/tree/1.0.27) (2018-01-30)
[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.0.26...1.0.27)

**Implemented enhancements:**

- Send categories in product vendor attribute [\#129](https://github.com/mailchimp/mc-magento2/issues/129)

**Fixed bugs:**

- Merge Names not synching [\#188](https://github.com/mailchimp/mc-magento2/issues/188)
- Error in ebizmarts\_ecommerce after upgrading to 1.0.26 [\#186](https://github.com/mailchimp/mc-magento2/issues/186)
- Installation error when the database has a prefix [\#184](https://github.com/mailchimp/mc-magento2/issues/184)

## [1.0.26](https://github.com/mailchimp/mc-magento2/tree/1.0.26) (2018-01-24)
[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.0.25...1.0.26)

**Implemented enhancements:**

- Copy suggestions for List Group enhancement [\#173](https://github.com/mailchimp/mc-magento2/issues/173)
- Add support for List Groups [\#122](https://github.com/mailchimp/mc-magento2/issues/122)
- Magento 2.2 compatibility [\#116](https://github.com/mailchimp/mc-magento2/issues/116)
- Add mergevars [\#110](https://github.com/mailchimp/mc-magento2/issues/110)

**Fixed bugs:**

- MailChimp connected sites List Settings syncing seemingly forever [\#66](https://github.com/mailchimp/mc-magento2/issues/66)
- Products are not marked as modified in the sync\_ecommerce table [\#177](https://github.com/mailchimp/mc-magento2/issues/177)
- Child product update when parent has not been sent yet [\#160](https://github.com/mailchimp/mc-magento2/issues/160)
- Guest Abandoned Carts failing to load when accessing from automation. [\#153](https://github.com/mailchimp/mc-magento2/issues/153)
- Guest Abandoned Carts not associating email address at checkout [\#152](https://github.com/mailchimp/mc-magento2/issues/152)
- Sync only works, if eCommerce is enabled [\#150](https://github.com/mailchimp/mc-magento2/issues/150)
- Intallation error in EE when the database is already splitted [\#149](https://github.com/mailchimp/mc-magento2/issues/149)
- Compatibility with Magento Enterprise Edition 2.1.x [\#144](https://github.com/mailchimp/mc-magento2/issues/144)
- Send duplicates promo codes [\#121](https://github.com/mailchimp/mc-magento2/issues/121)
- Mark customer as modified when any data was modified [\#118](https://github.com/mailchimp/mc-magento2/issues/118)
- Error when the webhook is created [\#117](https://github.com/mailchimp/mc-magento2/issues/117)
- Exception when running cron [\#114](https://github.com/mailchimp/mc-magento2/issues/114)
- Cron error "Requested country is not available." [\#58](https://github.com/mailchimp/mc-magento2/issues/58)
- Fix for broken admin grid in production [\#176](https://github.com/mailchimp/mc-magento2/pull/176) ([duckchip](https://github.com/duckchip))
- Module Dependancy [\#126](https://github.com/mailchimp/mc-magento2/pull/126) ([valguss](https://github.com/valguss))

## [1.0.25](https://github.com/mailchimp/mc-magento2/tree/1.0.25) (2017-11-06)
[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.0.24...1.0.25)

**Implemented enhancements:**

- Add support for Promo Rules and Promo Codes [\#100](https://github.com/mailchimp/mc-magento2/issues/100)
- Total subscribers in admin display the total subscriber of the account [\#94](https://github.com/mailchimp/mc-magento2/issues/94)
- Performance cron ebizmarts\_ecommerce [\#93](https://github.com/mailchimp/mc-magento2/issues/93)

**Fixed bugs:**

- The cart url for abandoned cart not work [\#111](https://github.com/mailchimp/mc-magento2/issues/111)
- Store is always syncing [\#97](https://github.com/mailchimp/mc-magento2/issues/97)
- Module doesn't install if database uses a prefix [\#95](https://github.com/mailchimp/mc-magento2/issues/95)

## [1.0.24](https://github.com/mailchimp/mc-magento2/tree/1.0.24) (2017-09-18)
[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.0.23...1.0.24)

**Implemented enhancements:**

- Major Admin Order Grid Slowdown [\#88](https://github.com/mailchimp/mc-magento2/issues/88)

**Fixed bugs:**

- Error installation when use split database [\#85](https://github.com/mailchimp/mc-magento2/issues/85)
- Incorrect cart url [\#84](https://github.com/mailchimp/mc-magento2/issues/84)
- Incorrect image url [\#82](https://github.com/mailchimp/mc-magento2/issues/82)
- Change $this-\>\_helper-\>\_\_\(\) to \_\_\(\) [\#80](https://github.com/mailchimp/mc-magento2/pull/80) ([rikardwissing](https://github.com/rikardwissing))

## [1.0.23](https://github.com/mailchimp/mc-magento2/tree/1.0.23) (2017-09-01)
[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.0.22...1.0.23)

**Fixed bugs:**

- Guest subscribers issue - STOREID is null [\#75](https://github.com/mailchimp/mc-magento2/issues/75)
- Website scope always taking list from default configuration [\#68](https://github.com/mailchimp/mc-magento2/issues/68)
- Requested product doesn't exist [\#67](https://github.com/mailchimp/mc-magento2/issues/67)
- Bad ajax call [\#65](https://github.com/mailchimp/mc-magento2/issues/65)
- Resubscribing a customer from Magento throws a 500 error due to Fatal Uncaught Error: "Call to a member function getStreetLine\(\) on string" [\#62](https://github.com/mailchimp/mc-magento2/issues/62)
- Display appropriate thumbnails for items [\#43](https://github.com/mailchimp/mc-magento2/issues/43)
- New customers are not added to MailChimp [\#42](https://github.com/mailchimp/mc-magento2/issues/42)
- remove the use of data helper from InstallSchema [\#70](https://github.com/mailchimp/mc-magento2/pull/70) ([BlackIkeEagle](https://github.com/BlackIkeEagle))

## [1.0.22](https://github.com/mailchimp/mc-magento2/tree/1.0.22) (2017-07-26)
[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.0.21...1.0.22)

## [1.0.21](https://github.com/mailchimp/mc-magento2/tree/1.0.21) (2017-07-14)
[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.0.20...1.0.21)

## [1.0.20](https://github.com/mailchimp/mc-magento2/tree/1.0.20) (2017-07-12)
[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.0.19...1.0.20)

## [1.0.19](https://github.com/mailchimp/mc-magento2/tree/1.0.19) (2017-07-12)
[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.0.18...1.0.19)

## [1.0.18](https://github.com/mailchimp/mc-magento2/tree/1.0.18) (2017-06-29)
[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.0.17...1.0.18)

**Implemented enhancements:**

- Add a commit call when use a connection directly [\#53](https://github.com/mailchimp/mc-magento2/issues/53)

**Fixed bugs:**

- Reset the errors when no more mailchimp store are connected [\#54](https://github.com/mailchimp/mc-magento2/issues/54)

## [1.0.17](https://github.com/mailchimp/mc-magento2/tree/1.0.17) (2017-06-21)
[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.0.16...1.0.17)

**Fixed bugs:**

- Error in mc.js when change the mailchimp store [\#51](https://github.com/mailchimp/mc-magento2/issues/51)
- Prevent sending customers to other store views even when they belong to the same website [\#48](https://github.com/mailchimp/mc-magento2/issues/48)

## [1.0.16](https://github.com/mailchimp/mc-magento2/tree/1.0.16) (2017-06-19)
[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.0.15...1.0.16)

**Implemented enhancements:**

- Limit the process of the webhooks [\#47](https://github.com/mailchimp/mc-magento2/issues/47)

**Fixed bugs:**

- Invalid product url on simple products not visible [\#49](https://github.com/mailchimp/mc-magento2/issues/49)

## [1.0.15](https://github.com/mailchimp/mc-magento2/tree/1.0.15) (2017-06-05)
[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.0.14...1.0.15)

**Implemented enhancements:**

- List MC account and List  [\#22](https://github.com/mailchimp/mc-magento2/issues/22)
- Add the download response link to error grid [\#8](https://github.com/mailchimp/mc-magento2/issues/8)
- Process the webhooks in a separate cron process [\#46](https://github.com/mailchimp/mc-magento2/issues/46)
- Show the list in the configuration page [\#41](https://github.com/mailchimp/mc-magento2/issues/41)
- Add actions to webhooks [\#40](https://github.com/mailchimp/mc-magento2/issues/40)
- Add webhooks [\#39](https://github.com/mailchimp/mc-magento2/issues/39)
- Request: Move MailChimp Store above Account Details on the Configuration page [\#31](https://github.com/mailchimp/mc-magento2/issues/31)
- Autoselect store when only one exists [\#28](https://github.com/mailchimp/mc-magento2/issues/28)
- Change the configuration to use jquery and ajax [\#27](https://github.com/mailchimp/mc-magento2/issues/27)
- Installation of MC.js pixel [\#18](https://github.com/mailchimp/mc-magento2/issues/18)

**Fixed bugs:**

- Ecomm Data uploaded to MailChimp should display if reselecting a connected store. [\#32](https://github.com/mailchimp/mc-magento2/issues/32)
- Account no longer syncing [\#26](https://github.com/mailchimp/mc-magento2/issues/26)
- Multiple stores not showing in Configuration dropdown [\#25](https://github.com/mailchimp/mc-magento2/issues/25)
- MC Account information displayed in Configuration does not change when API key is toggled. [\#24](https://github.com/mailchimp/mc-magento2/issues/24)
- Remove old warning message on Configuration [\#23](https://github.com/mailchimp/mc-magento2/issues/23)
- In the MailChimp Store, the street is not saved [\#21](https://github.com/mailchimp/mc-magento2/issues/21)
- Pull all lists for dropdown when creating a store [\#20](https://github.com/mailchimp/mc-magento2/issues/20)
- Use "MailChimp" instead of "Mailchimp" [\#19](https://github.com/mailchimp/mc-magento2/issues/19)
- Change the order \# when sync [\#45](https://github.com/mailchimp/mc-magento2/issues/45)
- Not mark the already synced element like errors [\#44](https://github.com/mailchimp/mc-magento2/issues/44)
- Avoid error when for some reason the process cancel [\#38](https://github.com/mailchimp/mc-magento2/issues/38)
- Review the Customer process [\#37](https://github.com/mailchimp/mc-magento2/issues/37)
- Problem when receive the response of batch [\#35](https://github.com/mailchimp/mc-magento2/issues/35)
- Add ACL permissions [\#34](https://github.com/mailchimp/mc-magento2/issues/34)
- Sorting MailChimp Stores grid by email more than once causes display issues [\#33](https://github.com/mailchimp/mc-magento2/issues/33)
- Enable not saving on a MailChimp store level [\#29](https://github.com/mailchimp/mc-magento2/issues/29)
- Invalid product image url when the product not have any image [\#17](https://github.com/mailchimp/mc-magento2/issues/17)
- Newly added products not synced. [\#13](https://github.com/mailchimp/mc-magento2/issues/13)
- Store information not passing to MailChimp [\#12](https://github.com/mailchimp/mc-magento2/issues/12)
- Purchases made via campaign not attributed to the campaign [\#11](https://github.com/mailchimp/mc-magento2/issues/11)
- APIKey management and store creation [\#10](https://github.com/mailchimp/mc-magento2/issues/10)

## [1.0.14](https://github.com/mailchimp/mc-magento2/tree/1.0.14) (2017-05-04)
[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.0.9...1.0.14)

## [1.0.9](https://github.com/mailchimp/mc-magento2/tree/1.0.9) (2017-05-03)
[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.0.8...1.0.9)

## [1.0.8](https://github.com/mailchimp/mc-magento2/tree/1.0.8) (2017-05-03)
[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.0.7...1.0.8)

## [1.0.7](https://github.com/mailchimp/mc-magento2/tree/1.0.7) (2017-05-02)
[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.0.6...1.0.7)

## [1.0.6](https://github.com/mailchimp/mc-magento2/tree/1.0.6) (2017-04-24)
[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.0.5...1.0.6)

## [1.0.5](https://github.com/mailchimp/mc-magento2/tree/1.0.5) (2017-04-21)
[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.0.4...1.0.5)

## [1.0.4](https://github.com/mailchimp/mc-magento2/tree/1.0.4) (2017-03-22)
[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.0.3...1.0.4)

**Fixed bugs:**

- The error grid don't paginate [\#9](https://github.com/mailchimp/mc-magento2/issues/9)
- Add the mailchimp\_store\_id to all auxiliar tables [\#7](https://github.com/mailchimp/mc-magento2/issues/7)
- The reset errors button not work [\#6](https://github.com/mailchimp/mc-magento2/issues/6)
- Error in mailchimp\_errors table [\#5](https://github.com/mailchimp/mc-magento2/issues/5)
- Products not syncing [\#3](https://github.com/mailchimp/mc-magento2/issues/3)

## [1.0.3](https://github.com/mailchimp/mc-magento2/tree/1.0.3) (2017-03-18)
[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.0.2...1.0.3)

**Fixed bugs:**

- Clean error don't work [\#4](https://github.com/mailchimp/mc-magento2/issues/4)

## [1.0.2](https://github.com/mailchimp/mc-magento2/tree/1.0.2) (2017-03-17)
[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.0.1...1.0.2)

## [1.0.1](https://github.com/mailchimp/mc-magento2/tree/1.0.1) (2017-03-16)
[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.0.0...1.0.1)

## [1.0.0](https://github.com/mailchimp/mc-magento2/tree/1.0.0) (2017-03-16)


\* *This Change Log was automatically generated by [github_changelog_generator](https://github.com/skywinder/Github-Changelog-Generator)*