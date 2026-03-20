<?php
/**
 * Cars API endpoint
 * Returns car listings as JSON for the frontend
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/config.php';

$pdo = getDB();

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'featured':
        // Get featured cars for homepage
        $stmt = $pdo->query("
            SELECT c.*,
                   (SELECT filename FROM car_images WHERE car_id=c.id AND is_primary=1 LIMIT 1) as image
            FROM cars c
            WHERE c.status='approved' AND c.is_featured=1
            ORDER BY c.created_at DESC
            LIMIT 6
        ");
        $cars = $stmt->fetchAll();
        break;

    case 'detail':
        // Get single car with all images
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Car ID required']);
            exit;
        }
        // Increment views
        $pdo->prepare("UPDATE cars SET views = views + 1 WHERE id = ?")->execute([$id]);

        $stmt = $pdo->prepare("SELECT * FROM cars WHERE id = ? AND status IN ('approved', 'sold')");
        $stmt->execute([$id]);
        $car = $stmt->fetch();

        if (!$car) {
            echo json_encode(['success' => false, 'message' => 'Car not found']);
            exit;
        }

        $imgStmt = $pdo->prepare("SELECT filename, is_primary FROM car_images WHERE car_id = ? ORDER BY is_primary DESC, sort_order");
        $imgStmt->execute([$id]);
        $car['images'] = $imgStmt->fetchAll();

        echo json_encode(['success' => true, 'car' => $car]);
        exit;

    case 'list':
    default:
        // List approved cars with optional filters
        $where = ["c.status = 'approved'"];
        $params = [];

        if (!empty($_GET['make'])) {
            $where[] = "c.make = ?";
            $params[] = $_GET['make'];
        }
        if (!empty($_GET['city'])) {
            $where[] = "c.city = ?";
            $params[] = $_GET['city'];
        }
        if (!empty($_GET['min_price'])) {
            $where[] = "c.price >= ?";
            $params[] = (int)$_GET['min_price'];
        }
        if (!empty($_GET['max_price'])) {
            $where[] = "c.price <= ?";
            $params[] = (int)$_GET['max_price'];
        }
        if (!empty($_GET['year'])) {
            $where[] = "c.year = ?";
            $params[] = (int)$_GET['year'];
        }
        if (!empty($_GET['fuel'])) {
            $where[] = "c.fuel_type = ?";
            $params[] = $_GET['fuel'];
        }
        if (!empty($_GET['transmission'])) {
            $where[] = "c.transmission = ?";
            $params[] = $_GET['transmission'];
        }
        if (!empty($_GET['search'])) {
            $where[] = "(c.title LIKE ? OR c.make LIKE ? OR c.model LIKE ?)";
            $search = '%' . $_GET['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $whereStr = implode(' AND ', $where);

        // Pagination
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(50, max(6, (int)($_GET['per_page'] ?? 12)));
        $offset = ($page - 1) * $perPage;

        // Sort
        $sortOptions = [
            'newest' => 'c.created_at DESC',
            'price_low' => 'c.price ASC',
            'price_high' => 'c.price DESC',
            'year_new' => 'c.year DESC',
            'year_old' => 'c.year ASC',
        ];
        $sort = $sortOptions[$_GET['sort'] ?? 'newest'] ?? $sortOptions['newest'];

        // Count
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM cars c WHERE $whereStr");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        // Fetch
        $stmt = $pdo->prepare("
            SELECT c.*,
                   (SELECT filename FROM car_images WHERE car_id=c.id AND is_primary=1 LIMIT 1) as image
            FROM cars c
            WHERE $whereStr
            ORDER BY $sort
            LIMIT $perPage OFFSET $offset
        ");
        $stmt->execute($params);
        $cars = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'cars' => $cars,
            'total' => (int)$total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage),
        ]);
        exit;
}

// For featured action
echo json_encode(['success' => true, 'cars' => $cars]);
