# Change Log

## [102.3.34](https://github.com/mailchimp/mc-magento2/tree/HEAD)

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