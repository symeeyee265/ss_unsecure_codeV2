<?php
include '../db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    // No input sanitization -> SQL Injection possible
    $query = "SELECT * FROM books WHERE title LIKE '%$search%' OR author LIKE '%$search%'";
} else {
    $query = "SELECT * FROM books";
}

$books = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Explore Books</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <h2>Explore Books</h2>

    <form method="get">
        <input type="text" name="search" placeholder="Search books..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">Search</button>
    </form>

    <br>

    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>Title</th>
            <th>Author</th>
            <th>Price</th>
            <th>Action</th>
        </tr>

        <?php
        while ($row = mysqli_fetch_assoc($books)) {
            echo "<tr>";
            echo "<td>".htmlspecialchars($row['title'])."</td>";
            echo "<td>".htmlspecialchars($row['author'] ?? '')."</td>";
            echo "<td>".$row['price']."</td>";
            echo "<td><a href='book_detail.php?id=".$row['id']."'>View Details</a></td>";
            echo "</tr>";
        }
        ?>
    </table>

    <br>
    <!-- Go Back Button -->
    <a href="index.php" style="display: inline-block; padding: 10px 15px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px;">🏠 Go Back to Home</a>

</body>
</html>
