<?php
    $conn = new mysqli("localhost", "root", "", "happytooth");
    if($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Membership ViewPage</title>
    <link rel="stylesheet" href="membership.css">
    <!-- Boxicons CDN -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <div class="container">
        <form method="GET" class="search-container" style="margin-top: 60px;">
            <input type="text" name="search" placeholder="Search Member IC" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            <select name="status">
                <option value="">Payment Status</option>
                <option value="Pending" <?= ($_GET['status'] ?? '') == 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="Completed" <?= ($_GET['status'] ?? '') == 'Completed' ? 'selected' : '' ?>>Completed</option>
            </select>
            <button type="submit" class="icon-button search"><i class='bx bx-search'></i></button>
            <button class="icon-button refresh"><i class='bx bx-refresh'></i></button>
        </form>

        <div class="table-wrapper" style="margin-top: 80px;">
            <table>
                <thead>
                    <tr>
                        <th>IC No.</th>
                        <th>Package Type</th>
                        <th>Package Status</th>
                        <th>Payment Amount</th>
                        <th>Payment Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $search = $_GET['search'] ?? '';
                        $status = $_GET['status'] ?? '';
                        $sql = "SELECT m.Member_IC, pk.Package_Type, pk.Package_Status, p.Payment_ID, p.Payment_Status, p.Payment_Amount
                                FROM Member m
                                JOIN Payment p ON p.Package_ID = m.Package_ID
                                JOIN Package pk ON pk.Package_ID = m.Package_ID
                                WHERE m.Member_IC LIKE ?";

                        $params = ["%$search%"];
                        $types = "s";

                        if ($status) {
                            $sql .= " AND p.Payment_Status = ?";
                            $params[] = $status;
                            $types .= "s";
                        }

                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param($types, ...$params);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $no = 1;

                        while ($row = $result->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($row['Member_IC']) ?></td>
                            <td><?= htmlspecialchars($row['Package_Type']) ?></td>
                            <td><?= htmlspecialchars($row['Package_Status']) ?></td>
                            <td>RM <?= number_format($row['Payment_Amount'], 2) ?></td>
                            <td><?= htmlspecialchars($row['Payment_Status']) ?></td>
                            <td>
                                <button class="icon-btn edit"><i class='bx bx-pencil'></i></button>
                                <button class="icon-btn delete"><i class='bx bx-trash'></i></button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>