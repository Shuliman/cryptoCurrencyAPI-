# Currency Data API

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

