# Cryptomania

A simple cryptocurrency portfolio tracker built with PHP, MySQL, and JavaScript.

## Features

- View top cryptocurrencies with real-time prices from CoinCap API
- Add cryptocurrencies to your personal wallet
- Manage wallet: update amounts, delete entries
- View portfolio value and individual holdings

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (e.g., Apache, Nginx)
- Local development stack like Laragon or XAMPP recommended

## Setup

1. Clone or download the repository to your web server's document root.

2. Create a MySQL database named `cryptomania`.

3. Create the `wallet` table with the following structure:

   ```sql
   CREATE TABLE wallet (
       id INT AUTO_INCREMENT PRIMARY KEY,
       date_bought DATE NOT NULL,
       coin_id VARCHAR(50) NOT NULL,
       coin_symbol VARCHAR(10) NOT NULL,
       coin_name VARCHAR(100) NOT NULL,
       price_usd DECIMAL(20,10) NOT NULL,
       amount DECIMAL(20,10) NOT NULL
   );
   ```

4. Update database credentials in `wallet.php` if necessary (default: localhost, root, no password).

5. Ensure the `assets/` directory is accessible and contains the CSS and JS files.

6. Open `Cryptomania.php` in your browser to start.

## Usage

- **Home/Market**: Browse cryptocurrencies, view details, add to wallet.
- **Wallet**: View and manage your portfolio.

## API

Uses CoinCap API v3 for cryptocurrency data. API key is included in `assets/cryptomania.js` (free tier).

## Files

- `Cryptomania.php`: Main market page
- `wallet.php`: Wallet management (API and UI)
- `assets/cryptomania.js`: Frontend JavaScript
- `assets/cryptomania.css`: Stylesheet

## License

MIT License