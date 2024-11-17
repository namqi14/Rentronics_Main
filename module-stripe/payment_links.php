<?php
require 'vendor/autoload.php';

\Stripe\Stripe::setApiKey('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');

try {
    // Fetch payment links
    $paymentLinks = \Stripe\PaymentLink::all(['limit' => 100]);

    // Initialize an array to store the data
    $paymentLinksData = [];
    
    // Loop through the payment links to get the details
    foreach ($paymentLinks->data as $link) {
        // Fetch the line items for each payment link
        $lineItems = \Stripe\PaymentLink::retrieve($link->id, ['expand' => ['line_items']])->line_items;
        
        foreach ($lineItems->data as $item) {
            $paymentLinksData[] = [
                'name' => $item->description,
                'price' => number_format($item->amount_total / 100, 2),
                'url' => $link->url,
            ];
        }
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
    die();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stripe Payment Links</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
</head>
<body>
    <table id="paymentLinksTable" class="display">
        <thead>
            <tr>
                <th>Name</th>
                <th>Price</th>
                <th>URL</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($paymentLinksData as $data): ?>
                <tr>
                    <td><?php echo htmlspecialchars($data['name']); ?></td>
                    <td><?php echo htmlspecialchars($data['price']); ?></td>
                    <td><a href="<?php echo htmlspecialchars($data['url']); ?>" target="_blank">Link</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
        $(document).ready(function() {
            $('#paymentLinksTable').DataTable();
        });
    </script>
</body>
</html>
