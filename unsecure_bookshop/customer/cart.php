<?php
include '../db.php';
session_start();

$user_id = $_SESSION['user_id']; // No validation if not logged in

// Delete cart item (no CSRF token, no ownership check!)
if (isset($_GET['delete'])) {
    $cart_id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM cart WHERE id = '$cart_id'");
    echo "<script>alert('Item removed!'); window.location='cart.php';</script>";
}

// Update cart quantity (allow negative quantity, no validation)
if (isset($_GET['update']) && isset($_GET['quantity'])) {
    $cart_id = $_GET['update'];
    $quantity = $_GET['quantity'];
    mysqli_query($conn, "UPDATE cart SET quantity = '$quantity' WHERE id = '$cart_id'");
    echo "<script>alert('Quantity updated!'); window.location='cart.php';</script>";
}

// Simulate receiving a message and inserting it into the database (with injection vulnerability)
if (isset($_POST['message'])) {
    $message = $_POST['message'];
    // check XSS script
    if (preg_match('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i', $message)) {
        echo "<script>alert('hahaha ,U got XSS injection'); window.location='cart.php';</script>";
    } else {
        // No input filtering, directly insert into the database
        mysqli_query($conn, "INSERT INTO seller_messages (user_id, message) VALUES ('$user_id', '$message')");
        echo "<script>alert('Message sent!'); window.location='cart.php';</script>";
    }
}

// Fetch user's cart
$cart_items = mysqli_query($conn, "SELECT cart.*, books.title, books.price 
                                   FROM cart 
                                   JOIN books ON cart.book_id = books.id 
                                   WHERE cart.user_id = '$user_id'");

// Calculate total
$total = 0;
$itemCount = 0;
$cartData = [];
while ($row = mysqli_fetch_assoc($cart_items)) {
    $subtotal = $row['price'] * $row['quantity'];
    $total += $subtotal;
    $itemCount++;
    $cartData[] = $row;
}
// Reset result pointer to start
mysqli_data_seek($cart_items, 0);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }

       .header1 {
            text-align: center;
            margin-bottom: 20px;
            background-color: #C5C5C5;
        }

       .main-content {
            display: flex;
            gap: 20px;
        }

       .cart-items {
            flex: 2;
        }

       .summary {
            flex: 1;
            background-color: #f0f0f0;
            padding: 20px;
        }

       .cart-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            border-bottom: 1px solid #ddd;
        }

       .cart-item-info {
            flex: 1;
        }

       .quantity-controls {
            display: flex;
            align-items: center;
            gap: 5px;
        }

       .quantity-controls button {
            padding: 5px 10px;
            background-color: #eee;
            border: 1px solid #ddd;
            cursor: pointer;
        }

       .quantity-controls input {
            width: 40px;
            text-align: center;
        }

       .cart-item-price {
            font-weight: bold;
        }

       .delete-btn {
            color: #ff0000;
            cursor: pointer;
        }

       .summary-item {
            margin-bottom: 15px;
        }

       .summary-item label {
            display: block;
            margin-bottom: 5px;
        }

       .summary-item input,
       .summary-item textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
        }

       .checkout-btn {
            width: 100%;
            padding: 10px;
            background-color: #000;
            color: #fff;
            border: none;
            cursor: pointer;
            margin-top: 20px;
        }

       .clickjacking-container {
            position: relative;
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
        }

       .clickjacking-image {
            width: 300px;
            height: auto;
        }

       .clickjacking-overlay {
            position: absolute;
            top: 0;
            left: auto;
            right: 0;
            width: 300px;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="header1">
        <h2>🛒Your Cart 🛒</h2>
    </div>
    <div class="main-content">
        <div class="cart-items">
            <div class="item-count">
                <?php echo "$itemCount items"; ?>
            </div>
            <?php
            foreach ($cartData as $index => $row) {
                $subtotal = $row['price'] * $row['quantity'];
            ?>
                <div class="cart-item">
                    <div class="cart-item-info">
                        <h4><?php echo $row['title']; ?></h4>
                        <div class="quantity-controls">
                            <button onclick="changeQuantity(<?php echo $index; ?>, -1)"><i class="fas fa-minus"></i></button>
                            <input type="number" value="<?php echo $row['quantity']; ?>" onchange="updateQuantity(<?php echo $index; ?>, this.value)">
                            <button onclick="changeQuantity(<?php echo $index; ?>, 1)"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                    <div class="cart-item-price">€ <span id="subtotal-<?php echo $index; ?>"><?php echo number_format($subtotal, 2); ?></span></div>
                    <div class="delete-btn" onclick="deleteItem(<?php echo $row['id']; ?>)"><i class="fas fa-times"></i></div>
                </div>
            <?php
            }
            ?>
            <a href="explore.php" class="back-to-shop">← Back to shop</a>
        </div>
        <div class="summary">
            <h3>Summary</h3>
            <div class="summary-item">
                <label>ITEMS</label>
                <span id="item-count"><?php echo $itemCount; ?></span>
                <span id="item-total">€ <?php echo number_format($total, 2); ?></span>
            </div>
            <div class="summary-item">
                <label>MESSAGE TO SELLER</label>
                <form action="cart.php" method="post">
                    <textarea name="message" placeholder="Type your message here"></textarea>
                    <input type="submit" value="Send Message">
                </form>
            </div>
            <div class="summary-item">
                <label>TOTAL PRICE</label>
                <span id="total-price">€ <?php echo number_format($total, 2); ?></span>
            </div>
            <button class="checkout-btn" onclick="window.location.href='checkout.php'">CHECKOUT</button>
        </div>
    </div>
    <div class="clickjacking-container">
        <img class="clickjacking-image" src="https://picsum.photos/300/400" alt="Random Image">
        <a href="fakewebsite.php" class="clickjacking-overlay"></a>
    </div>

    <script>
        const cartData = <?php echo json_encode($cartData); ?>;

        function changeQuantity(index, delta) {
            const input = document.querySelector(`input[onchange*='updateQuantity(${index},']`);
            let quantity = parseInt(input.value);
            quantity += delta;
            input.value = quantity;
            updateQuantity(index, quantity);
        }

        function updateQuantity(index, quantity) {
            const price = cartData[index].price;
            const subtotal = price * quantity;
            const subtotalElement = document.getElementById(`subtotal-${index}`);
            subtotalElement.textContent = subtotal.toFixed(2);

            let newTotal = 0;
            const quantityInputs = document.querySelectorAll('.quantity-controls input');
            quantityInputs.forEach((input, i) => {
                const itemQuantity = parseInt(input.value);
                const itemPrice = cartData[i].price;
                newTotal += itemPrice * itemQuantity;
            });

            const itemTotalElement = document.getElementById('item-total');
            const totalPriceElement = document.getElementById('total-price');
            itemTotalElement.textContent = `€ ${newTotal.toFixed(2)}`;
            totalPriceElement.textContent = `€ ${newTotal.toFixed(2)}`;

            // Send a request to update the database
            const cartId = cartData[index].id;
            window.location.href = `cart.php?update=${cartId}&quantity=${quantity}`;
        }

        function deleteItem(itemId) {
            if (confirm('Are you sure you want to remove this item?')) {
                window.location.href = `cart.php?delete=${itemId}`;
            }
        }

        // test xss
        document.addEventListener('DOMContentLoaded', function() {
            const messages = document.querySelectorAll('.summary-item textarea[name="message"]');
            messages.forEach(function(message) {
                const value = message.value;
                if (/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i.test(value)) {
                    alert('hahaha ,U got XSS injection');
                }
            });
        });
    </script>
</body>

</html>    