NTU Geocaching Server
===========

NTU Geocaching Server is a RESTful API backend originally used in the Freshman Club Fair of National Taiwan University.

The system enables participants to swipe their student ID card through NFC card readers located at booth of cooperating clubs. After visiting a certain number of booths, participants can get a special gift.

The system consists of two roles

- **Endpoint**: An endpoint basically represents a booth, it could also be seen as a tourist attraction for user to check-in

- **User**: An user basically represents an individual participant, which is also a player of the game

## Getting Started

Before you get started, you'll have to set up the following two things

- **Database**: The system uses database to store records of endpoints, users, and visits. The **DATABASE_URL** should be set in environment variables. For Heroku Postgres users, just make sure you've added the database addon.

- **Master Key**: The master key is used to identify the system manager. The key should be encrypted using SHA1, named **MASTER_KEY_SHA1** located in environment variables.

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


- **Get statistics for endpoint**: To get statistics for specified endpoint

		GET /endpoint/[ Name of Endpoint ] HTTP/1.1

## Contributer

- Shouko <shouko@ntuosc.org>
