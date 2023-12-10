# Crypto Currency Exchange API

## Overview
This web application provides an API for retrieving cryptocurrency rates, specifically for Bitcoin (BTC) against various currencies like USD, EUR, etc. The API allows fetching exchange rate data hourly and supports adjustable data range outputs, catering to use cases such as cryptocurrency rate chart plotting.

## Features
- Retrieve BTC/USD, BTC/EUR, and more currency pair rates.
- Option to modify the range of data retrieval.

## Technologies Used
- Symfony 7
- PHP 8.2
- MySQL 8

## Installation

### Prerequisites
- PHP 8.2 or higher
- MySQL 8
- Composer

### Steps
1. Clone the repository:
   `git clone https://github.com/Shuliman/cryptoCurrencyAPI-.git`
   `cd cryptoCurrencyAPI`


2. Install dependencies:
   `composer install`

3. Set up environment variables:
- Configure the `.env` file with your database and API settings.

4. Database setup:
- Create a MySQL database named `crypto_currency`.
- Run migrations:
  ```
  php bin/console doctrine:migrations:migrate
  ```

## Running the Application
Start the Symfony server using:
`symfony serve`

Access the application at `http://127.0.0.1:8000` (or the port indicated in your terminal).

## API Usage

Currency Data API provides access to currency exchange rate data. The API includes several endpoints to retrieve data for different time intervals.

### Controllers

### HourlyDataController

- **Description**: Returns exchange rate data for a specific hour.
- **Query Example**: `GET /currency/hourly?fsym=BTC&tsym=USD`.
- **Parameters**:
    - `fsym` (string): Base currency symbol (e.g., BTC).
    - `tsym` (string): Quote currency symbol (e.g. USD).
    - `hour` (string, optional): The hour for receiving data in the format `YYYYY-MM-DD HH:00:00`. The default is the last hour.

### DailyDataController

- **Description**: Returns exchange rate data for a specific day.
- **Example Query**: `GET /currency/daily?fsym=BTC&tsym=USD&date=2023-11-03`.
- **Parameters**:
    - `fsym` (string): base currency symbol.
    - `tsym` (string): Quote currency symbol.
    - `date` (string, optional): Date to receive data in `YYYYYY-MM-DD` format. The default is the current day.

### WeeklyDataController

- **Description**: Returns exchange rate data for a specific week.
- **Example Query**: `GET /currency/weekly?fsym=BTC&tsym=PLN&week=2023-10-13`.
- **Parameters**:
    - `fsym` (string): base currency symbol.
    - `tsym` (string): Quote currency symbol.
    - `week` (string, optional): The date of the middle of the requested week to receive data in `YYYYYY-MM-DD` format. The default is the current date.

