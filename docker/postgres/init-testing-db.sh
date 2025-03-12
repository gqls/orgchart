#!/bin/bash
set -e

# Create the testing database if it doesn't exist
psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    CREATE DATABASE orgcharts_testing;
    GRANT ALL PRIVILEGES ON DATABASE orgcharts_testing TO $POSTGRES_USER;
EOSQL

# Connect to the testing database and set up extensions if needed
psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "orgcharts_testing" <<-EOSQL
    CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
EOSQL

echo "Testing database created successfully!"