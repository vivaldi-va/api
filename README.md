api
===

The Ostosnero API

#Standard return model format
```
{
  "success":  boolean,
  "error":    String OR null,
  "message":  String OR null,
  "data":     array OR null
}
```

* success: determines whether or not the API function has succeeded or not
  * if not, it will return false and should add an error string
* error: a string identifier relaying an error in the API function
* message: additional information relating to the function if needed. Usually null.
* data: the data returned from the function if relevant, otherwise it's null.



#User

##Get user session
*GET* /user/session
* params: none
* returns: return model, user info in "data" if success else "error"

```
{
  "email":  string (e.g. 'example@ostosnero.com'),
  "name":   string (e.g. 'some guy'),
  "stats": {
      "shopping_trips": int,
      "total_saved":    float,
      "total_spent":    float
    }
}
```

> "success" determines whether the user is logged in or not
> if successfully determined to be logged in, "data" is populated with user info.


##User login
*POST* /user/login
* params:
  * "email": user login email 
  * "password": user login password
* returns: See user session (returns the same stuff)


##User registration
*POST* /user/register

##User logout
*GET* /user/logout

#Product

##Get product info
*GET* /product/:productId

##Get product prices
*GET* /product/prices/:productId/:latitude/:longitude

##Update product price at a location
*GET* /product/prices/update/:productId/:shopId/:price


