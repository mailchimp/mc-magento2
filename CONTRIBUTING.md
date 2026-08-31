### Contribution requirements

1. Contributions must adhere to [Magento coding standards](http://devdocs.magento.com/guides/v2.0/coding-standards/bk-coding-standards.html).
2. Pull requests (PRs) must be accompanied by a meaningful description of their purpose. Comprehensive descriptions increase the chances that a pull request is merged quickly and without additional clarification requests.
3. Commits must be accompanied by meaningful commit messages. (include a **closes** in the commit that close the issue)
4. PRs that include bug fixing must be accompanied by the creation of an issue describing the bug following this [Issue reporting guidelines](https://github.com/mailchimp/mc-magento2/wiki/Issue-reporting-guidelines).
5. For large features or changes, please open an issue and discuss first. This may prevent duplicate or unnecessary effort, and it may gain you some additional contributors.
6. PRs that claim a performance change must show the measurement, and the measurement must be taken after `ANALYZE TABLE`. **A newly created index benchmarks worse than no index until the optimiser's statistics catch up**, so the first result you get is often the opposite of the true one — an index that turned out to be 28x faster first measured 2.5x slower, and that number very nearly went into a review. This is not specific to any one table or index.
