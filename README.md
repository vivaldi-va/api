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

##Search for products
*GET* /search/:term

##Get product info
*GET* /product/:productId

##Get product prices
*GET* /product/prices/:productId/:latitude/:longitude

##Update product price at a location
*GET* /product/prices/update/:productId/:shopId/:price


#List

##Get user list
*GET* /list

##Add product to list
*GET* /list/add/:productId

##Remove product from list
*GET* /list/remove/:listItemId

##Update list quantity
*GET* /list/quantity/:listItemId/:quantity

##Sort the user's list
*GET* /list/sort/:latitude/:longitude

##Sort the user's list based on their chosen locations (no coords)
*GET* /list/sort


#Location

##Get closest locations
*GET* /location/:latitude/:longitude

##Get location information
*GET* /location/info/:latitude/:longitude

##Search for locations
*GET* /location/search/:keywords

##Get saved locations
*GET* /location/saved

##Add location to saved list
*GET* /location/add/:shopId

##Remove location from saved list
*GET* /location/remove/:shopId