
<p align="center">
<img style="width:300px; height:300px;"src="https://raw.githubusercontent.com/alejandro-carstens/apriori-recommendation-engine/master/public/images/ARERA_LOGO.png"/>
</p>

# What is ARERA?

*    <b>ARERA</b> stands for <b>A</b>priori <b>R</b>ecommendation <b>E</b>ngine <b>R</b>ESTful <b>A</b>PI
*    In simple words it is an apriori-algorithm-based recommendation engine API build with Laravel/Lumen & Redis
*    It implements an apriori-like algorithm in order to obtain association rules to make predictions or recommendations. Please note that this API is not an implementation of the apriori algorithm, it was just inspired by it

# Index

*    [Objective](https://github.com/alejandro-carstens/apriori-recommendation-engine/#objective)
*    [Intentions](https://github.com/alejandro-carstens/apriori-recommendation-engine/#intentions)
*    [A-Transaction-in-the-Scope-of-the-API](https://github.com/alejandro-carstens/apriori-recommendation-engine/#a-transactionin-the-scope-of-the-api)


# Objective:

*    The goal of this project is to create the most user friendly and simplest recommendation engine API while remaining highly effective, reliable, fast, and scalable

# Intentions:

*    The API must be developed in an elegant, maintainable, and scalable way, following SOLID coding principles as much as possible
*    The API must be fully tested
*    The API must be free, open source, and subjected to the MIT License
*    The API is to be hosted on Amazon Web Services (AWS) leveraging the power of Elasticache, Elastic Beanstalk & RDS

# A Transaction in the Scope of The API

## Definition:
*   In this context, a transaction is the registration of the occurrence of an event or activity in which one or multiple identifiable items or elements have participated
## Examples:
*   A purchase order, a search string, a list of viewed videos, a medical diagnosis, etc.
## JSON Representation:
```javascript
    {
     "id": "1",
     "table_id": "1",
     "items": [
        "123456",
        "123457",
        "123458",
     ],
     "created_at": "2017-05-27 23:04:02",
     "updated_at": "2017-05-27 23:04:02",
    }
```
# Association Rules

## What are association rules?

*    Association rules are simple if-then statements that help us find relationships between seemingly unrelated data in an information repository. An example of an association rule would be "If a customer buys a 12-pack of beer, he is 80% likely to also purchase chips."

## Ways to Measure Association Used By the API

*    This API makes use of 3 ways to measure association: [support](https://github.com/alejandro-carstens/apriori-recommendation-engine/#support), [confidence](https://github.com/alejandro-carstens/apriori-recommendation-engine/#confidence), and [lift](https://github.com/alejandro-carstens/apriori-recommendation-engine/#lift)

### Support

*   Specifies how popular an itemset is, as measured by the proportion of transactions in which an itemset appears. In <b>Table 1</b>, the support of {apple} is 4 out of 8, or 50%. Itemsets can also contain multiple items. For instance, the support of {apple, beer, rice} is 2 out of 8, or 25%

<p  align="center"> 
<img src="https://annalyzin.files.wordpress.com/2016/03/association-rule-support-eqn.png?w=248&h=68" />
<br>
<img src="https://annalyzin.files.wordpress.com/2016/04/association-rule-support-table.png?w=503&h=447" />
<br> 
<b>Table 1</b>
</p>

### Confidence

*   This says how likely item Y is going to be in a transaction when item X is going to be in a transaction, expressed as {X -> Y}. This is measured by the proportion of transactions with item X, in which item Y also appears. In <b>Table 1</b>, the confidence of {apple -> beer} is 3 out of 4, or 75%

<p  align="center"> 
<img src="https://annalyzin.files.wordpress.com/2016/03/association-rule-confidence-eqn.png?w=527&h=77" />
<br>
</p>

### Lift
*   This says how likely item Y is going to be in a transaction when item X is going to be in a transaction, while controlling for how popular item Y is. In <b>Table 1</b>, the lift of {apple -> beer} is 1, which implies no association between items. A lift value greater than 1 means that item Y is likely to be bought if item X is bought, while a value less than 1 means that item Y is unlikely to be bought if item X is bought

<p  align="center"> 
<img src="https://annalyzin.files.wordpress.com/2016/03/association-rule-lift-eqn.png?w=566&h=80" />
<br>
</p>

# Quick Algorithm Overview

## Assumptions:

*    A user has been created and this one is associated with a TRANSACTIONS zset (a Redis sorted set) key and a COMBINATIONS zset key

## Storing Transactions and Combinations

1.    The user sends a POST request to store a transaction
2.    The transaction’s combinations are generated respecting a combination max-size constraint so that we do not run out of memory and remain efficient (#of combinations=2^𝑛−1+𝑛)
3.    Store the combinations in Redis and increment their associated frequency (score) via the ZINCRBY Redis Command
4.    Store the transaction in Redis and in a Relational Database

## Obtaining Association Recommendations/Predictions:

1.    The user sends a GET request passing the item or itemset for which the association rules must be obtained
2.    Using the ZSCAN Redis command, we obtain the combinations for each of the items passed with their associated frequency. We then filter out the non associated combinations to then calculate the support for each of the remaining combinations
3.    The lift and confidence rules are generated and the recommendation results are then returned with an ordered defined by their confidence score

# To Do List

*   More unit testing
*   Add the option to queue combinations generation when storing a transaction
*   Set the lumen environment for AWS Elastic Beanstalk Deployment
*   Deploy a beta version of the API and try it out

# Notice:

I recently began this project. I haven't gotten the time to write a more complete documentation on what it is trying to do. However, I think the code is somewhat readable, intuitive, and easy to understand. I encourage everyone interested to give me their opionions and to contribute. I will do my best to document this project over the next few days, and to promote it around developers to see if it can get some traction. Thanks in advance and I hope to see some pull requests soon.

# Contributing 

Find an area you can help with and do it. Open source is about collaboration and open participation. Try to make your code look like what already exists or better and submit a pull request. Also, in general if you have any ideas on how to make the code better or on improving its scope and functionality please contact me.

# Lisence

MIT Lisence.
