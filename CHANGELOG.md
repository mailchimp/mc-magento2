# Change Log

## [1.0.28](https://github.com/mailchimp/mc-magento2/tree/1.0.28) (2018-03-27)

[Full Changelog](https://github.com/mailchimp/mc-magento2/compare/1.0.27...1.0.28)

**Implemented enhancements:**

- Not necessary mailchimp/script/get requests [\#248](https://github.com/mailchimp/mc-magento2/issues/248)
- Add a button to create the webhooks [\#229](https://github.com/mailchimp/mc-magento2/issues/229)
- Add get Api credentials button using oauth. [\#207](https://github.com/mailchimp/mc-magento2/issues/207)
- Special price management [\#194](https://github.com/mailchimp/mc-magento2/issues/194)
- Use a checkbox on Checkout to determine Opt-in status. [\#36](https://github.com/mailchimp/mc-magento2/issues/36)

**Fixed bugs:**

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
- Show "Mailchimp" customer tab when the extension is enabled [\#201](https://github.com/mailchimp/mc-magento2/pull/201) ([t-richards](https://github.com/t-richards))
- Update default.xml [\#180](https://github.com/mailchimp/mc-magento2/pull/180) ([jhruehl](https://github.com/jhruehl))

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