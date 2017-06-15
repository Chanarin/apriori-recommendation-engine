
<img src="https://travis-ci.org/alejandro-carstens/apriori-recommendation-engine.svg?branch=master" />
<img src="https://raw.githubusercontent.com/hyn/multi-tenant/2.x/license.md" />

<p  align="center"> 
<img src="https://github.com/alejandro-carstens/apriori-recommendation-engine/blob/master/public/images/logo.png" />
<br>
</p>

# What is ARERA?

*    <b>ARERA</b> stands for <b>A</b>priori <b>R</b>ecommendation <b>E</b>ngine <b>R</b>ESTful <b>A</b>PI
*    In simple words it is an apriori-algorithm-based recommendation engine API build with Laravel/Lumen & Redis
*    It implements an apriori-like algorithm in order to obtain association rules to make predictions or recommendations. Please note that this API is not an implementation of the apriori algorithm, it was just inspired by it

# Index

*    [Objective](https://github.com/alejandro-carstens/apriori-recommendation-engine/#objective)
*    [Intentions](https://github.com/alejandro-carstens/apriori-recommendation-engine/#intentions)
*    [A Transaction in the Scope of the API](https://github.com/alejandro-carstens/apriori-recommendation-engine/#a-transactionin-the-scope-of-the-api)
*    [Association Rules](https://github.com/alejandro-carstens/apriori-recommendation-engine/#association-rules)
*    [Quick Algorithm Overview](https://github.com/alejandro-carstens/apriori-recommendation-engine/#quick-algorithm-overview)
*    [API Endpoints](https://github.com/alejandro-carstens/apriori-recommendation-engine/#api-endpoints)
     *    [Users](https://github.com/alejandro-carstens/apriori-recommendation-engine/#users)
     *    [Credentials](https://github.com/alejandro-carstens/apriori-recommendation-engine/#credentials)
     *    [Redis Keys](https://github.com/alejandro-carstens/apriori-recommendation-engine/#redis-keys)
     *    [Transactions](https://github.com/alejandro-carstens/apriori-recommendation-engine/#transactions)
     *    [Apriori](https://github.com/alejandro-carstens/apriori-recommendation-engine/#apriori)
*    [To Do List](https://github.com/alejandro-carstens/apriori-recommendation-engine/#to-do-list)
*    [Notice](https://github.com/alejandro-carstens/apriori-recommendation-engine/#notice)
*    [Contributing](https://github.com/alejandro-carstens/apriori-recommendation-engine/#contributing)
*    [Liscense](https://github.com/alejandro-carstens/apriori-recommendation-engine/#liscense)

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

# API Endpoints

## Users

### POST /users

<b>Action:</b> Create user <br>
<b>Parameters:</b> name, email, password, password_confirmation <br>
<b>Scope: </b> Admin <br>
<b>Response:</b>
```javascript
{
  "data": {
    "name": "Alex",
    "email": "carstens@gmail.com",
    "secret": "2fc6270535e0b3f8ee180d7466ba9415e054850f",
    "client": "72f93f10532be890d2d16f189395fa35718aa9d4",
    "id": 5
  }
}
```
### GET /users/{user_id}?access_token=ACCESS_TOKEN
<b>Action:</b> Retrieve user details <br>
<b>Scope:</b> Resource owner <br>
<b>Response:</b>
```javascript
{
  "data": {
    "name": "Alex",
    "email": "carstens@gmail.com",
    "secret": "2fc6270535e0b3f8ee180d7466ba9415e054850f",
    "client": "72f93f10532be890d2d16f189395fa35718aa9d4",
    "id": 5
  }
}
```

### PUT or PATCH /users/{user_id}

<b>Action:</b> Update user <br>
<b>Parameters:</b> name, email, password, password_confirmation <br>
<b>Scope: </b> Resource owner <br>
<b>Response:</b>
```javascript
{
    "data": {
        "message": "User with id 11 updated successfully.",
        "data": {
            "id": 11,
            "name": "Alex",
            "email": "chingon.alex@gmail.com",
            "client": "37acea3e599492738e79e2b575480de1b7866db4",
            "secret": "2f3d4cfb78f5ffab0de84473f8a319c7bc379059"
        }
    }
}
```

### DELETE /users/{user_id}
<b>Parameters:</b> access_token <br>
<b>Scope: </b> Resource owner <br>
<b>Response:</b>
```javascript
{
  "data": "User with id 5 successfully deleted."
}
```

## Credentials

### PATCH or PUT /users/{user_id}/credentials
<b>Parameters:</b> access_token <br>
<b>Scope: </b> Resource owner <br>
<b>Response:</b>
```javascript
{
    "data": {
        "message": "User with id 11 credentials updated successfully.",
        "data": {
            "id": 11,
            "name": "Alex",
            "email": "chingon.carstennis@gmail.com",
            "client": "37acea3e599492738e79e2b575480de1b7866db4",
            "secret": "2f3d4cfb78f5ffab0de84473f8a319c7bc379059"
        }
    }
}
```

## Redis Keys

### POST /users/{user_id}/redis_keys
<b>Parameters:</b> access_token, master_key <br>
<b>Scope: </b> Resource owner <br>
<b>Response:</b>
```javascript
{
    "data": {
        "message": "Key with id 9 created successfully.",
        "data": {
            "master_key": "cookie jar",
            "transactions_key": "transactions-cookie jar-1497219222",
            "combinations_key": "combinations-cookie jar-1497219222",
            "updated_at": "2017-06-11 22:13:42",
            "created_at": "2017-06-11 22:13:42",
            "id": 9
        }
    }
}
```

### GET /users/{user_id}/redis_keys?access_token=ACCESS_TOKEN
<b>Scope: </b> Resource owner <br>
<b>Response:</b>
```javascript
{
  "data": {
    "results": [
      {
        "id": 2,
        "master_key": "alex_key",
        "transactions_key": "transactions-alex_key-1497073608",
        "combinations_key": "combinations-alex_key-1497073608",
        "created_at": "2017-06-10 05:46:48",
        "updated_at": "2017-06-10 05:46:48"
      }
    ],
    "paginator": {
      "total_count": 1,
      "total_pages": 1,
      "current_page": 1,
      "limit": 100,
      "next_page_url": null,
      "previous_page_url": null
    }
  }
}
```
### GET /redis_keys/{redis_key_id}?access_token=ACCESS_TOKEN
<b>Scope: </b> Resource owner <br>
<b>Response:</b>
```javascript
{
  "data": {
        "id": 4,
        "master_key": "guera",
        "transactions_key": "transactions-guera-1497205317",
        "combinations_key": "combinations-guera-1497205317",
        "created_at": "2017-06-11 18:21:57",
        "updated_at": "2017-06-11 18:21:57"
   }
}
```

### PATCH or PUT /users/{user_id}/redis_keys/{redis_key_id}
<b>Parameters:</b> access_token, master_key <br>
<b>Scope: </b> Resource owner <br>
<b>Response:</b>
```javascript
{
    "data": {
        "message": "Key with id 6 has been updated.",
        "data": {
            "id": 6,
            "master_key": "lupua",
            "transactions_key": "transactions-lupua-1497219375",
            "combinations_key": "combinations-lupua-1497219375",
            "created_at": "2017-06-11 21:02:35",
            "updated_at": "2017-06-11 22:16:15"
        }
    }
}
```

### DELETE /users/{user_id}/redis_keys/{redis_key_id}
<b>Parameters:</b> access_token <br>
<b>Scope: </b> Resource owner <br>
<b>Response:</b>
```javascript
{
    "data": "The Redis key with id 6 has been removed from user 11"
}
```
## Transactions

### POST /redis_keys/{redis_keys_id}/transactions
<b>Parameters:</b> access_token, items[] <br>
<b>Scope: </b> Resource owner <br>
<b>Response:</b>
```javascript
{
    "data": {
        "message": "Transaction with id 63 created successfully.",
        "data": {
            "items": [
                "1",
                "1111",
                "11"
            ],
            "redis_key_id": 8,
            "updated_at": "2017-06-11 22:00:49",
            "created_at": "2017-06-11 22:00:49",
            "id": 63
        }
    }
}
```

### POST /redis_keys/{redis_keys_id}/transactions_async
<b>Parameters:</b> access_token, items[] <br>
<b>Scope: </b> Resource owner <br>
<b>Response:</b>
```javascript
{
    "data": {
        "message": "Transaction with id 63 created successfully.",
        "data": {
            "items": [
                "1",
                "1111",
                "11"
            ],
            "redis_key_id": 8,
            "updated_at": "2017-06-11 22:00:49",
            "created_at": "2017-06-11 22:00:49",
            "id": 63
        }
    }
}
```

### GET /redis_keys/{redis_keys_id}/transactions?access_token=ACCESS_TOKEN
<b>Scope: </b> Resource owner <br>
<b>Response:</b>

```javascript
{
    "data": {
        "results": [
            {
                "id": 9,
                "redis_key_id": 4,
                "items": [
                    "1",
                    "2",
                    "7",
                    "8",
                    "14"
                ],
                "created_at": "2017-06-11 18:43:59",
                "updated_at": "2017-06-11 18:43:59"
            },
            {
                "id": 10,
                "redis_key_id": 4,
                "items": [
                    "11",
                    "1",
                    "7",
                    "18",
                    "24"
                ],
                "created_at": "2017-06-11 18:45:05",
                "updated_at": "2017-06-11 18:45:05"
            }
        ],
        "paginator": {
            "total_count": 2,
            "total_pages": 1,
            "current_page": 1,
            "limit": 100,
            "next_page_url": null,
            "previous_page_url": null
        }
    }
}
```
### GET /transactions/{transaction_id}?access_token=ACCESS_TOKEN
<b>Scope: </b> Resource owner <br>
<b>Response:</b>

```javascript
{
    "data": {
        "id": 11,
        "redis_key_id": 4,
        "items": [
            "11",
            "1",
            "7",
            "18",
            "24"
        ],
        "created_at": "2017-06-11 18:55:52",
        "updated_at": "2017-06-11 18:55:52"
    }
}
```
### PATCH or PUT /redis_keys/{redis_key_id}/transactions/{transaction_id}
<b>Parameters:</b> access_token, items[] <br>
<b>Scope: </b> Resource owner <br>
<b>Response:</b>
```javascript
{
    "data": {
        "message": "Transaction with id 63 was updated successfully.",
        "data": {
            "id": 63,
            "redis_key_id": 8,
            "items": [
                "22",
                "2"
            ],
            "created_at": "2017-06-11 22:00:49",
            "updated_at": "2017-06-11 22:19:39"
        }
    }
}
```

### DELETE /redis_keys/{redis_key_id}/transactions/{transaction_id}
<b>Parameters:</b> access_token<br>
<b>Scope: </b> Resource owner <br>
<b>Response:</b>
```javascript
{
    "data": "The transaction with id 10 was successfully deleted."
}
```

## Apriori

### GET /redis_keys/{redis_key_id}/apriori=?access_token=ACCESS_TOKEN&items[]={item_1}&items[]={item_2}
<b>Scope: </b> Resource owner <br>
<b>Response:</b>
```javascript
{
    "data": [
        {
            "lift": 1,
            "confidence": 0.66666666666667,
            "support": 1,
            "key": [
                "11"
            ]
        },
        {
            "lift": 1,
            "confidence": 0.66666666666667,
            "support": 1,
            "key": [
                "1"
            ]
        },
        {
            "lift": 1,
            "confidence": 0.33333333333333,
            "support": 1,
            "key": [
                "71"
            ]
        },
        {
            "lift": 1,
            "confidence": 0.33333333333333,
            "support": 1,
            "key": [
                "12"
            ]
        }
    ]
}
```

# To Do List

*   More unit testing - in progress
*   Set the lumen environment for AWS Elastic Beanstalk Deployment - done but needs some tune up
*   Add Travis
*   Add Scrutinizer 
*   Add StyleCI
*   Promote the API beta version for testing and collaboration
*   Deploy a beta version of the API and try it out - you can hit the API at [http://arera-test.us-west-2.elasticbeanstalk.com/](http://arera-test.us-west-2.elasticbeanstalk.com)

# Notice:

I recently began this project. I haven't gotten the time to write a more complete documentation on what it is trying to do. However, I think the code is somewhat readable, intuitive, and easy to understand. I encourage everyone interested to give me their opionions and to contribute. I will do my best to document this project over the next few days, and to promote it around developers to see if it can get some traction. Thanks in advance and I hope to see some pull requests soon.

# Contributing 

Find an area you can help with and do it. Open source is about collaboration and open participation. Try to make your code look like what already exists or better and submit a pull request. Also, in general if you have any ideas on how to make the code better or on improving its scope and functionality please contact me.

# Lisence

MIT Lisence.
