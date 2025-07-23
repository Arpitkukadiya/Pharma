<?php
@include 'config.php';

session_start();

$user_id = $_SESSION['user_id'];

if (!isset($user_id)) {
    header('location:login.php');
    exit();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

if (isset($_POST['order'])) {
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $number = filter_var($_POST['number'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_STRING);
    $method = filter_var($_POST['method'], FILTER_SANITIZE_STRING);
    $address = 'Flat No. ' . $_POST['flat'] . ', ' . $_POST['street'] . ', ' . $_POST['city'] . ', ' . $_POST['state'] . ', ' . $_POST['country'] . ' - ' . $_POST['pin_code'];
    $address = filter_var($address, FILTER_SANITIZE_STRING);
    $placed_on = date('d-M-Y');

    $cart_total = 0;
    $cart_products = [];

    $cart_query = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
    $cart_query->execute([$user_id]);

    if ($cart_query->rowCount() > 0) {
        while ($cart_item = $cart_query->fetch(PDO::FETCH_ASSOC)) {
            $cart_products[] = $cart_item;
            $cart_total += $cart_item['price'] * $cart_item['quantity'];
        }
    }

    $product_list = array_map(function ($item) {
        return $item['name'] . ' (' . $item['quantity'] . ')';
    }, $cart_products);
    $total_products = implode(', ', $product_list);

    $order_query = $conn->prepare("SELECT * FROM `orders` WHERE name = ? AND number = ? AND email = ? AND method = ? AND address = ? AND total_products = ? AND total_price = ?");
    $order_query->execute([$name, $number, $email, $method, $address, $total_products, $cart_total]);

    if ($cart_total == 0) {
        echo "<script>alert('Your cart is empty!');</script>";
    } elseif ($order_query->rowCount() > 0) {
        echo "<script>alert('Order has already been placed!');</script>";
    } else {
        $insert_order = $conn->prepare("INSERT INTO `orders`(user_id, name, number, email, method, address, total_products, total_price, placed_on) VALUES(?,?,?,?,?,?,?,?,?)");
        $insert_order->execute([$user_id, $name, $number, $email, $method, $address, $total_products, $cart_total, $placed_on]);

        $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE user_id = ?");
        $delete_cart->execute([$user_id]);

        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username   = 'automail424@gmail.com';     
            $mail->Password   = 'bbzwnuogpqilhbaw';  
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('your_email@gmail.com', 'Phrama');
            $mail->addAddress($email, $name);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Order Confirmation - Pharma';
            $mail->Body = "
            <html>
                <head>
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            background-color: #f9f9f9;
                            margin: 0;
                            padding: 0;
                            color: #333;
                        }
                        .email-container {
                            max-width: 600px;
                            margin: 20px auto;
                            background: #ffffff;
                            border: 1px solid #ddd;
                            border-radius: 8px;
                            overflow: hidden;
                            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                        }
                        .email-header {
                            background: #007bff;
                            color: white;
                            padding: 20px;
                            text-align: center;
                            font-size: 24px;
                            font-weight: bold;
                        }
                        .email-body {
                            padding: 20px;
                        }
                        .email-body p {
                            margin: 10px 0;
                            font-size: 16px;
                        }
                        .order-table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-top: 20px;
                        }
                        .order-table th, .order-table td {
                            border: 1px solid #ddd;
                            padding: 10px;
                            text-align: left;
                        }
                        .order-table th {
                            background: #f4f4f4;
                            font-weight: bold;
                        }
                        .order-table tr:nth-child(even) {
                            background: #f9f9f9;
                        }
                        .total {
                            font-size: 18px;
                            font-weight: bold;
                            margin-top: 20px;
                            color: #007bff;
                        }
                        .footer {
                            text-align: center;
                            font-size: 14px;
                            color: #888;
                            margin-top: 20px;
                            padding: 10px;
                            border-top: 1px solid #ddd;
                        }
                    </style>
                </head>
                <body>
                    <div class='email-container'>
                        <div class='email-header'>Order Confirmation</div>
                        <div class='email-body'>
                            <p>Hi <strong>$name</strong>,</p>
                            <p>Thank you for your order placed on <strong>$placed_on</strong>. Here are your order details:</p>
                            <table class='order-table'>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                </tr>";
            foreach ($cart_products as $cart_item) {
                $mail->Body .= "<tr>
                    <td>{$cart_item['name']}</td>
                    <td>{$cart_item['quantity']}</td>
                    <td>₹{$cart_item['price']}/-</td>
                </tr>";
            }
            $mail->Body .= "
                            </table>
                            <p class='total'>Total: ₹$cart_total/-</p>
                            <p><strong>Shipping Address:</strong> $address</p>
                            <p>We hope to serve you again soon!</p>
                        </div>
                        <div class='footer'>
                            &copy; " . date('Y') . " FoodZone. All rights reserved.
                        </div>
                    </div>
                </body>
            </html>";

            $mail->send();
            echo "<script>alert('Your order has been placed successfully. A confirmation email has been sent.'); window.location.href = 'checkout.php';</script>";
        } catch (Exception $e) {
            echo "<script>alert('Error sending confirmation email: {$mail->ErrorInfo}');</script>";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>checkout</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <style>
       body {
         background-color: #f4f7f8;
         font-family: 'Roboto', sans-serif;
         margin: 0;
         padding: 0;
      }

      .display-orders, .checkout-orders {
         margin: 20px;
         padding: 20px;
         background: #fff;
         border-radius: 10px;
         box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
      }

      .display-orders p, .checkout-orders h3 {
         font-size: 1.2rem;
         color: #333;
      }

      .grand-total {
         font-size: 1.5rem;
         color: #ff5722;
         text-align: center;
         margin-top: 10px;
      }

      .empty {
         color: #777;
         font-size: 1.2rem;
         text-align: center;
      }

      .checkout-orders form {
         display: flex;
         flex-direction: column;
         gap: 20px;
      }

      .inputBox {
         display: flex;
         flex-direction: column;
         gap: 5px;
      }

      .inputBox input, .inputBox select {
         padding: 10px;
         border: 1px solid #ccc;
         border-radius: 5px;
         font-size: 1rem;
      }

      .btn {
         padding: 10px;
         border: none;
         background: #1a73e8;
         color: white;
         border-radius: 5px;
         cursor: pointer;
         font-size: 1rem;
         text-align: center;
      }

      .btn:hover {
         background: #0056b3;
      }

      .btn.disabled {
         background: #ccc;
         pointer-events: none;
      }
      </style>

</head>
<body>
   
<?php include 'header.php'; ?>

<section class="display-orders">

   <?php
      $cart_grand_total = 0;
      $select_cart_items = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
      $select_cart_items->execute([$user_id]);
      if($select_cart_items->rowCount() > 0){
         while($fetch_cart_items = $select_cart_items->fetch(PDO::FETCH_ASSOC)){
            $cart_total_price = ($fetch_cart_items['price'] * $fetch_cart_items['quantity']);
            $cart_grand_total += $cart_total_price;
   ?>
   <p> <?= $fetch_cart_items['name']; ?> <span>(<?= '₹'.$fetch_cart_items['price'].'/- x '. $fetch_cart_items['quantity']; ?>)</span> </p>
   <?php
    }
   }else{
      echo '<p class="empty">your cart is empty!</p>';
   }
   ?>
   <div class="grand-total">grand total : <span>₹<?= $cart_grand_total; ?>/-</span></div>
</section>

<section class="checkout-orders">

   <form action="" method="POST">

      <h3>place your order</h3>

      <div class="flex">
         <div class="inputBox">
            <span>your name :</span>
            <input type="text" name="name" placeholder="enter your name" class="box" required>
         </div>
         <div class="inputBox">
            <span>your number :</span>
            <input type="number" name="number" placeholder="enter your number" class="box" required>
         </div>
         <div class="inputBox">
            <span>your email :</span>
            <input type="email" name="email" placeholder="enter your email" class="box" required>
         </div>
         <div class="inputBox">
            <span>payment method :</span>
            <select name="method" class="box" required>
               <option value="cash on delivery">cash on delivery</option>
               <option value="credit card">credit card</option>
             
               <option value="paytm">paytm</option>
               <option value="paypal">paypal</option>
            </select>
         </div>
         
         <div class="inputBox">
            <span>address line 01 :</span>
            <input type="text" name="flat" placeholder="e.g. flat number" class="box" required>
         </div>
         <div class="inputBox">
            <span>address line 02 :</span>
            <input type="text" name="street" placeholder="e.g. street name" class="box" required>
         </div>
         <div class="inputBox">
            <span>city :</span>
            <input type="text" name="city" placeholder="e.g. Gandhinagar" class="box" required>
         </div>
         <div class="inputBox">
            <span>state :</span>
            <input type="text" name="state" placeholder="e.g. gujarat" class="box" required>
         </div>
         <div class="inputBox">
            <span>country :</span>
            <input type="text" name="country" placeholder="e.g. India" class="box" required>
         </div>
         <div class="inputBox">
            <span>pin code :</span>
            <input type="number" min="0" name="pin_code" placeholder="e.g. 382016" class="box" required>
         </div>
      </div>
      <input type="submit" name="order" class="btn <?= ($cart_grand_total > 1)?'':'disabled'; ?>" value="place order">

   </form>

</section>
</body>
</html>
