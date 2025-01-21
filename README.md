# WHMCS-Order-Fetcher [![Discord](https://img.shields.io/badge/Join%20Our%20Discord-7289DA?style=for-the-badge&logo=discord&logoColor=white)](https://discord.void-dev.co)

A Simple PHP Class I Wrote To Fetch Order ID's Via The Order Number, Yes I Know, It Could Probably Be Better But So What

## Example
```php
// Initialize
$orderFetcher = new WHMCSOrderFetcher();

// Retrieve the order number from the query parameters
$orderNumber = $_GET['ordernum'] ?? null;

// Check if the order number is provided and fetch the order
if ($orderNumber) {
    $orderFetcher->fetchOrder($orderNumber);
} else {
    exit('Error: Plesse Enter An Order Number!');
}

```

