<?php
@include 'config.php';

session_start();

$user_id = $_SESSION['user_id'];

if (!isset($user_id)) {
    header('location:login.php');
}

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $delete_orders = $conn->prepare("DELETE FROM `orders` WHERE id = ?");
    $delete_orders->execute([$delete_id]);
    header('location:orders.php');
}

function generateAndDownloadPDF($order_id)
{
    require('fpdf/fpdf.php');

    // Fetch order details from the database
    global $conn;
    $select_order = $conn->prepare("SELECT * FROM `orders` WHERE id = ?");
    $select_order->execute([$order_id]);
    $fetch_order = $select_order->fetch(PDO::FETCH_ASSOC);

    // Create a PDF document
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetMargins(10, 10, 10);

    // Add header with background color
    $pdf->SetFillColor(0, 123, 255); // Blue background
    $pdf->SetTextColor(255, 255, 255); // White text
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->Cell(0, 15, 'Hotel FoodZone - Invoice', 0, 1, 'C', true);

    // Add shop details
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetTextColor(0, 0, 0); // Black text
    $pdf->Ln(5);
    $pdf->Cell(0, 10, 'Address: 123, Main Street, City Name', 0, 1, 'C');
    $pdf->Cell(0, 10, 'Contact: +91 12345 67890', 0, 1, 'C');
    $pdf->Ln(10);

    // Order details section with a table
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'Order Details', 0, 1, 'L');
    $pdf->Ln(2);

    // Define table headers
    $pdf->SetFillColor(240, 240, 240); // Light gray background for table headers
    $pdf->Cell(50, 10, 'Field', 1, 0, 'C', true);
    $pdf->Cell(140, 10, 'Details', 1, 1, 'C', true);

    // Add order details
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(50, 10, 'Order ID', 1, 0, 'L');
    $pdf->Cell(140, 10, $order_id, 1, 1, 'L');
    $pdf->Cell(50, 10, 'Placed On', 1, 0, 'L');
    $pdf->Cell(140, 10, date('Y-m-d H:i:s', strtotime($fetch_order['placed_on'])), 1, 1, 'L');
    $pdf->Cell(50, 10, 'Payment Method', 1, 0, 'L');
    $pdf->Cell(140, 10, ucfirst($fetch_order['method']), 1, 1, 'L');
    $pdf->Cell(50, 10, 'Total Products', 1, 0, 'L');
    $pdf->Cell(140, 10, $fetch_order['total_products'], 1, 1, 'L');
    $pdf->Cell(50, 10, 'Total Price', 1, 0, 'L');
    $pdf->Cell(140, 10, '₹' . number_format($fetch_order['total_price'], 2), 1, 1, 'L');
    $pdf->Cell(50, 10, 'Payment Status', 1, 0, 'L');
    $pdf->Cell(140, 10, ucfirst($fetch_order['payment_status']), 1, 1, 'L');
    $pdf->Ln(10);

    // Customer details section
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'Customer Details', 0, 1, 'L');
    $pdf->Ln(2);

    // Customer details table
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(50, 10, 'Name', 1, 0, 'L');
    $pdf->Cell(140, 10, $fetch_order['name'], 1, 1, 'L');
    $pdf->Cell(50, 10, 'Number', 1, 0, 'L');
    $pdf->Cell(140, 10, $fetch_order['number'], 1, 1, 'L');
    $pdf->Cell(50, 10, 'Email', 1, 0, 'L');
    $pdf->Cell(140, 10, $fetch_order['email'], 1, 1, 'L');

    // Address section using MultiCell for longer text
    $pdf->Cell(50, 10, 'Address', 1, 0, 'L');
    $pdf->MultiCell(140, 10, $fetch_order['address'], 1, 'L');
    $pdf->Ln(10);

    // Footer section
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetTextColor(0, 123, 255); // Blue text
    $pdf->Cell(0, 10, 'Thank you for choosing Hotel FoodZone. Visit again!', 0, 1, 'C');
    $pdf->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');

    // Save and output the PDF
    $pdf->Output('order_bill_' . $order_id . '.pdf', 'D');
}

// Check if the "Print Bill" button was clicked
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['print_bill'])) {
    $order_id = $_POST['order_id'];
    generateAndDownloadPDF($order_id);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>orders</title>

    <!-- font awesome cdn link  -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

    <!-- custom css file link  -->
    <link rel="stylesheet" href="css/style.css">
<style>
 /* General Styling for the Page */
body {
    font-family: 'Roboto', sans-serif;
    background-color: #f4f7f8;
    margin: 0;
    padding: 0;
}

/* Section Styling for Placed Orders */
.placed-orders {
    padding: 30px;
    text-align: center;
    background-color: #ffffff;
}

.placed-orders .title {
    font-size: 2.5rem;
    color: #333;
    margin-bottom: 20px;
    font-weight: bold;
}

/* Card Container Styling */
.box-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    padding: 20px;
}

/* Individual Card Styling */
.box {
    background-color: #fff;
    border-radius: 15px;
  /*  box-shadow: 0px 4px 15px rgba(213, 152, 30, 0.1);
    */padding: 25px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    overflow: hidden;
    position: relative;
}

.box:hover {
    transform: translateY(-10px);
    box-shadow: 0px 6px 20pxhsl(145, 65.10%, 46.10%);
}

/* Text Styling inside the Cards */
.box p {
    font-size: 1.1rem;
    margin: 10px 0;
    color: white;
    line-height: 1.5;
}

.box span {
    font-weight: bold;
    color:#c79321;
}

/* Buttons Styling */
.flex-btn {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
}

.option-btn,
.delete-btn {
    padding: 12px 20px;
    border-radius: 8px;
    font-size: 1rem;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.3s ease;
    display: inline-block;
}

/* "Print Bill" Button Styling */
.option-btn {
    background-color: #28a745;
    color: #fff;
    border: none;
}

.option-btn:hover {
    background-color: #218838;
}

/* "Delete" Button Styling */
.delete-btn {
    background-color: #dc3545;
    color: #fff;
    border: none;
}

.delete-btn:hover {
    background-color: #c82333;
}

/* Empty State Styling */
.empty {
    color: #d9534f;
    font-size: 1.5rem;
    font-weight: bold;
}

/* Payment Status Styling */
.box p span[style="color: red"] {
    color: #ff3b3b;
}

.box p span[style="color: green"] {
    color: #28a745;
}


    </style>
</head>
<body>

<?php include 'header.php'; ?>

<section class="placed-orders">

    <h1 class="title">placed orders</h1>

    <div class="box-container">

        <?php
        $select_orders = $conn->prepare("SELECT * FROM `orders` WHERE user_id = ?");
        $select_orders->execute([$user_id]);
        if ($select_orders->rowCount() > 0) {
            while ($fetch_orders = $select_orders->fetch(PDO::FETCH_ASSOC)) {
                ?>
                <div class="box">
                    <p> placed on : <span><?= $fetch_orders['placed_on']; ?></span> </p>
                    <p> name : <span><?= $fetch_orders['name']; ?></span> </p>
                    <p> number : <span><?= $fetch_orders['number']; ?></span> </p>
                    <p> email : <span><?= $fetch_orders['email']; ?></span> </p>
                    <p> address : <span><?= $fetch_orders['address']; ?></span> </p>
                    <p> payment method : <span><?= $fetch_orders['method']; ?></span> </p>
                    <p> your orders : <span><?= $fetch_orders['total_products']; ?></span> </p>
                    <p> total price : <span>₹<?= $fetch_orders['total_price']; ?></span> </p>
                    <p> payment status : <span
                                style="color:<?php if ($fetch_orders['payment_status'] == 'pending') {
                                    echo 'red';
                                } else {
                                    echo 'green';
                                } ?>"><?= $fetch_orders['payment_status']; ?></span> </p>
                    <form action="" method="POST">
                        <div class="flex-btn">
                            <input type="hidden" name="order_id" value="<?= $fetch_orders['id']; ?>">
                            <input type="submit" name="print_bill" class="option-btn" value="Print Bill">
                            <a href="orders.php?delete=<?= $fetch_orders['id']; ?>"
                               class="delete-btn" onclick="return confirm('delete this order?');">delete</a>
                        </div>
                    </form>
                </div>
                <?php
            }
        } else {
            echo '<p class="empty">no orders placed yet!</p>';
        }
        ?>

    </div>

</section>

<?php include 'footer.php'; ?>

<script src="js/script.js"></script>

</body>
</html>
