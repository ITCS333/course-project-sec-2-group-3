function getWeekById(PDO $db, $id): void
{
    if (!$id || !is_numeric($id)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid week ID'
        ], 400);
    }

    $stmt = $db->prepare("
        SELECT id, title, start_date, description, links, created_at
        FROM weeks
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    $week = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($week) {

        $week['links'] = json_decode($week['links'], true) ?? [];

        sendResponse([
            'success' => true,
            'data' => $week
        ]);
    }

    sendResponse([
        'success' => false,
        'message' => 'Week not found'
    ], 404);
}

function createWeek(PDO $db, array $data): void
{
    if (
        empty($data['title']) ||
        empty($data['start_date'])
    ) {
        sendResponse([
            'success' => false,
            'message' => 'Title and start_date are required'
        ], 400);
    }

    $title = sanitizeInput($data['title']);
    $start_date = trim($data['start_date']);
    $description = sanitizeInput($data['description'] ?? '');

    if (!validateDate($start_date)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid date format'
        ], 400);
    }

    $links = [];

    if (isset($data['links']) && is_array($data['links'])) {
        $links = $data['links'];
    }

    $stmt = $db->prepare("
        INSERT INTO weeks (title, start_date, description, links)
        VALUES (?, ?, ?, ?)
    ");

    $success = $stmt->execute([
        $title,
        $start_date,
        $description,
        json_encode($links)
    ]);

    if ($success) {

        sendResponse([
            'success' => true,
            'message' => 'Week created successfully',
            'id' => $db->lastInsertId()
        ], 201);
    }

    sendResponse([
        'success' => false,
        'message' => 'Failed to create week'
    ], 500);
}

function updateWeek(PDO $db, array $data): void
{
    if (empty($data['id'])) {
        sendResponse([
            'success' => false,
            'message' => 'Week ID is required'
        ], 400);
    }

    $id = $data['id'];

    $check = $db->prepare("SELECT id FROM weeks WHERE id = ?");
    $check->execute([$id]);

    if (!$check->fetch()) {
        sendResponse([
            'success' => false,
            'message' => 'Week not found'
        ], 404);
    }

    $fields = [];
    $values = [];

    if (isset($data['title'])) {
        $fields[] = "title = ?";
        $values[] = sanitizeInput($data['title']);
    }

    if (isset($data['start_date'])) {

        if (!validateDate($data['start_date'])) {
            sendResponse([
                'success' => false,
                'message' => 'Invalid date format'
            ], 400);
        }

        $fields[] = "start_date = ?";
        $values[] = $data['start_date'];
    }

    if (isset($data['description'])) {
        $fields[] = "description = ?";
        $values[] = sanitizeInput($data['description']);
    }

    if (isset($data['links'])) {
        $fields[] = "links = ?";
        $values[] = json_encode($data['links']);
    }

    if (empty($fields)) {
        sendResponse([
            'success' => false,
            'message' => 'No fields to update'
        ], 400);
    }

    $values[] = $id;

    $sql = "
        UPDATE weeks
        SET " . implode(', ', $fields) . "
        WHERE id = ?
    ";

    $stmt = $db->prepare($sql);

    if ($stmt->execute($values)) {

        sendResponse([
            'success' => true,
            'message' => 'Week updated successfully'
        ]);
    }

    sendResponse([
        'success' => false,
        'message' => 'Failed to update week'
    ], 500);
}

function deleteWeek(PDO $db, $id): void
{
    if (!$id || !is_numeric($id)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid week ID'
        ], 400);
    }

    $check = $db->prepare("SELECT id FROM weeks WHERE id = ?");
    $check->execute([$id]);

    if (!$check->fetch()) {
        sendResponse([
            'success' => false,
            'message' => 'Week not found'
        ], 404);
    }

    $stmt = $db->prepare("DELETE FROM weeks WHERE id = ?");

    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {

        sendResponse([
            'success' => true,
            'message' => 'Week deleted successfully'
        ]);
    }

    sendResponse([
        'success' => false,
        'message' => 'Failed to delete week'
    ], 500);
}

function getCommentsByWeek(PDO $db, $weekId): void
{
    if (!$weekId || !is_numeric($weekId)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid week ID'
        ], 400);
    }

    $stmt = $db->prepare("
        SELECT id, week_id, author, text, created_at
        FROM comments_week
        WHERE week_id = ?
        ORDER BY created_at ASC
    ");

    $stmt->execute([$weekId]);

    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse([
        'success' => true,
        'data' => $comments
    ]);
}

function createComment(PDO $db, array $data): void
{
    if (
        empty($data['week_id']) ||
        empty($data['author']) ||
        empty(trim($data['text']))
    ) {
        sendResponse([
            'success' => false,
            'message' => 'week_id, author and text are required'
        ], 400);
    }

    $week_id = $data['week_id'];

    if (!is_numeric($week_id)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid week ID'
        ], 400);
    }

    $check = $db->prepare("SELECT id FROM weeks WHERE id = ?");
    $check->execute([$week_id]);

    if (!$check->fetch()) {
        sendResponse([
            'success' => false,
            'message' => 'Week not found'
        ], 404);
    }

    $author = sanitizeInput($data['author']);
    $text = sanitizeInput($data['text']);

    $stmt = $db->prepare("
        INSERT INTO comments_week (week_id, author, text)
        VALUES (?, ?, ?)
    ");

    $stmt->execute([
        $week_id,
        $author,
        $text
    ]);

    if ($stmt->rowCount() > 0) {

        sendResponse([
            'success' => true,
            'message' => 'Comment created successfully',
            'id' => $db->lastInsertId(),
            'data' => [
                'week_id' => $week_id,
                'author' => $author,
                'text' => $text
            ]
        ], 201);
    }

    sendResponse([
        'success' => false,
        'message' => 'Failed to create comment'
    ], 500);
}

function deleteComment(PDO $db, $commentId): void
{
    if (!$commentId || !is_numeric($commentId)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid comment ID'
        ], 400);
    }

    $check = $db->prepare("
        SELECT id
        FROM comments_week
        WHERE id = ?
    ");

    $check->execute([$commentId]);

    if (!$check->fetch()) {

        sendResponse([
            'success' => false,
            'message' => 'Comment not found'
        ], 404);
    }

    $stmt = $db->prepare("
        DELETE FROM comments_week
        WHERE id = ?
    ");

    $stmt->execute([$commentId]);

    if ($stmt->rowCount() > 0) {

        sendResponse([
            'success' => true,
            'message' => 'Comment deleted successfully'
        ]);
    }

    sendResponse([
        'success' => false,
        'message' => 'Failed to delete comment'
    ], 500);
}
