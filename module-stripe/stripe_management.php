<?php
require '../vendor/autoload.php';

use Stripe\Stripe;
use Stripe\Product;
use Stripe\PaymentLink;
use Stripe\Price;

Stripe::setApiKey('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'); // Replace with your Stripe secret key

try {
    $productData = [];

    // Retrieve all active products with pagination
    $productMap = [];
    $hasMoreProducts = true;
    $startingAfterProduct = null;

    while ($hasMoreProducts) {
        $productParams = ['active' => true, 'limit' => 100];
        if ($startingAfterProduct) {
            $productParams['starting_after'] = $startingAfterProduct;
        }
        $products = Product::all($productParams);
        foreach ($products->data as $product) {
            if (isset($product->metadata['Room ID'])) {
                // Retrieve the price for the product
                $prices = Price::all(['product' => $product->id, 'active' => true]);
                $price = isset($prices->data[0]) ? number_format($prices->data[0]->unit_amount / 100, 2) : 'N/A'; // Convert cents to dollars and format
                $currency = isset($prices->data[0]) ? strtoupper($prices->data[0]->currency) : '';

                // Format price to "RM 150.00" style
                if ($currency == 'MYR') {
                    $formattedPrice = 'RM ' . $price;
                } else {
                    $formattedPrice = $price . ' ' . $currency;
                }

                $productMap[$product->metadata['Room ID']] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => $formattedPrice,
                ];
            }
        }
        $hasMoreProducts = $products->has_more;
        if ($hasMoreProducts) {
            $startingAfterProduct = end($products->data)->id;
        }
    }

    // Retrieve all active payment links with pagination
    $hasMorePaymentLinks = true;
    $startingAfterPaymentLink = null;

    while ($hasMorePaymentLinks) {
        $paymentLinkParams = ['limit' => 100];
        if ($startingAfterPaymentLink) {
            $paymentLinkParams['starting_after'] = $startingAfterPaymentLink;
        }
        $paymentLinks = PaymentLink::all($paymentLinkParams);
        foreach ($paymentLinks->data as $paymentLink) {
            if (!$paymentLink->active) {
                continue; // Skip inactive payment links
            }

            $roomId = $paymentLink->metadata['Room ID'] ?? 'Not set';

            if ($roomId != 'Not set' && isset($productMap[$roomId])) {
                $productDetails = $productMap[$roomId];
                $productData[] = [
                    'name' => $productDetails['name'],
                    'description' => $productDetails['description'],
                    'price' => $productDetails['price'],
                    'payment_link' => $paymentLink->url
                ];
            }
        }
        $hasMorePaymentLinks = $paymentLinks->has_more;
        if ($hasMorePaymentLinks) {
            $startingAfterPaymentLink = end($paymentLinks->data)->id;
        }
    }

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stripe Products and Payment Links</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#productTable').DataTable();
        });
    </script>
</head>
<body>
    <div class="container">
        <h2>Stripe Products and Payment Links</h2>
        <table id="productTable" class="display">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Payment Link</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productData as $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo htmlspecialchars($product['description']); ?></td>
                        <td><?php echo htmlspecialchars($product['price']); ?></td>
                        <td><a href="<?php echo htmlspecialchars($product['payment_link']); ?>" target="_blank">Payment Link</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
