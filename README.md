NTU Geocaching Server
===========

NTU Geocaching Server is a RESTful API backend originally used in the Freshmen Club Fair of National Taiwan University.

The system enables participants to swipe their student ID card through NFC card readers located at booth of cooperating clubs. After visiting a certain number of booths, participants can get a special gift.

The system consists of two roles

- **Endpoint**: An endpoint basically represents a booth, it could also be seen as a tourist attraction for user to check-in

- **User**: An user basically represents an individual participant, which is also a player of the game

## Getting Started

Before you get started, you'll have to set up the following two things

- **Database**: The system uses database to store records of endpoints, users, and visits. The **DATABASE_URL** should be set in environment variables. For Heroku Postgres users, just make sure you've added the database addon.

- **Dependencies**: The system uses **Composer** to manage library dependencies. Make sure you have **Composer** installed, and run the command **composer install** in Terminal.

- **Master Key**: The master key is used to identify the system manager. The key should be encrypted using SHA1, named **MASTER_KEY_SHA1** located in environment variables.

- **Goal**: An integer greater than 0 should be set in environment variables named **REDEEM_REQ** to determine how many endpoint should user visit before redeeming a prize.

## Usage

- **Add / Update an endpoint**: To add an endpoint, or update it's key

		POST /endpoint HTTP/1.1

	The following POST parameters are required:

	- **auth**: Master key, in plain text
	- **name**: Endpoint name to add or update
	- **key**: Desired key to endpoint, which will be used to add check-in records in the future, in plain text


- **Add check-in record**: To add a check-in record to specified endpoint

		POST /endpoint/[ Name of Endpoint ] HTTP/1.1

	The following POST parameters are required:

	- **auth**: Key to endpoint, in plain text
	- **cuid**: Identification of participant, usually UID of NFC card


- **Register user info**: To add an user's personal info into database

		POST /user HTTP/1.1

	Notice: This registration process is not required prior to any check-in action, it can be done any time before redeeming prize.

	The following POST parameters are available:

	- **name**: Name of endpoint, optional, required only if the registration process is done by endpoint

	- **auth**: Master key, or key to endpoint if the parameter above is specified, in plain text

	- **cuid**: Identification of participant, usually UID of NFC card

	- **data**: Personal info of participant, may be student ID number, or a string at any length

- **Reddem prize for user**: To redeem prize for user

		POST /redeem HTTP/1.1

	Notice: A registration process should be done prior to this redeem action

	The following POST parameters are available:

	- **name**: Name of endpoint, optional, required only if the redeem process is done by endpoint

	- **auth**: Master key, or key to endpoint if the parameter above is specified, in plain text

	- **cuid**: Identification of participant, usually UID of NFC card

- **Get overall statistics**: To get an overall statistic

		GET / HTTP/1.1

- **Get statistics for endpoint**: To get statistics for specified endpoint

		GET /endpoint/[ Name of Endpoint ] HTTP/1.1

- **Get a list of all players**: To get the list of players

		GET /user HTTP/1.1

- **Get statistics for specified user**: To get statistics for specified user

		GET /user/[ Identification of User ] HTTP/1.1

## Contributor

- [Shouko](https://github.com/Shouko)
