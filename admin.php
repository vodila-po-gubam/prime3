<?php

declare(strict_types=1);

session_start();

require __DIR__ . '/app/AdminController.php';

$error = '';

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$_POST['username'] ?? '']);
    $user = $stmt->fetch();

    if ($user && $user['role'] === 'admin' && password_verify($_POST['password'] ?? '', $user['password'])) {
        $_SESSION['admin'] = true;
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header('Location: admin.php');
        exit;
    }
    $error = 'Неверное имя пользователя или пароль';
}

$authenticated = $_SESSION['admin'] ?? false;

if ($authenticated):
    $filter = $_GET['filter'] ?? '';
    $page   = max(1, (int)($_GET['page'] ?? 1));

    $data       = $controller->getPaginated($filter, $page);
    $rows       = $data['rows'];
    $total      = $data['total'];
    $totalPages = $data['total_pages'];
    $types      = $controller->getTypes();

    $labels = [
        'consult' => 'Консультация (главная)',
        'contact' => 'Обратная связь',
        'review'  => 'Отзыв',
        'auto'    => 'Деньги под авто',
        'service' => 'Заявка на услугу',
        'svo'     => 'Участникам СВО',
    ];
endif;

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админка | PrimeMoney</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background: #f7fafc;
        color: #2d3748;
    }

    .header {
        background: linear-gradient(135deg, #2f855a 0%, #48bb78 100%);
        color: white;
        padding: 15px 0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .header .container {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .logo {
        display: flex;
        align-items: center;
        font-size: 24px;
        font-weight: 700;
    }

    .logo i {
        margin-right: 10px;
        font-size: 28px;
        color: #e2e8f0;
    }

    .logo span {
        color: #e2e8f0;
    }

    .header-right {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .header-right a,
    .header-right span {
        color: white;
        text-decoration: none;
        font-weight: 500;
        font-size: 14px;
        padding: 8px 16px;
        border-radius: 6px;
        transition: all 0.3s;
    }

    .header-right a:hover {
        background: rgba(255,255,255,0.15);
        color: #fecaca;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .login-wrap {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: calc(100vh - 80px);
    }

    .login-box {
        background: white;
        border-radius: 12px;
        padding: 40px;
        max-width: 400px;
        width: 100%;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        border: 1px solid #e2e8f0;
        position: relative;
    }

    .login-box:before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #38a169 0%, #dc2626 100%);
        border-radius: 12px 12px 0 0;
    }

    .login-box h2 {
        text-align: center;
        margin-bottom: 10px;
        font-size: 22px;
        color: #2d3748;
    }

    .login-box p {
        text-align: center;
        color: #718096;
        font-size: 14px;
        margin-bottom: 25px;
    }

    .login-box .field {
        margin-bottom: 18px;
    }

    .login-box label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
        font-size: 13px;
        color: #4a5568;
    }

    .login-box input {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        font-size: 15px;
        transition: border-color 0.3s;
    }

    .login-box input:focus {
        outline: none;
        border-color: #38a169;
        box-shadow: 0 0 0 2px rgba(56,161,105,0.15);
    }

    .login-box .btn {
        width: 100%;
        padding: 13px;
        background: #38a169;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .login-box .btn:hover {
        background: #dc2626;
    }

    .login-box .error {
        background: #fff5f5;
        color: #dc2626;
        padding: 10px 15px;
        border-radius: 6px;
        font-size: 14px;
        margin-bottom: 18px;
        border: 1px solid #fecaca;
        text-align: center;
    }

    .filters {
        padding: 25px 0 15px;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }

    .filters a,
    .filters span {
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        text-decoration: none;
        transition: all 0.2s;
    }

    .filters a {
        background: white;
        color: #2f855a;
        border: 1px solid #e2e8f0;
    }

    .filters a:hover {
        border-color: #dc2626;
        color: #dc2626;
    }

    .filters a.active {
        background: #dc2626;
        color: white;
        border-color: #dc2626;
    }

    .table-wrap {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        overflow-x: auto;
        margin-bottom: 20px;
        border: 1px solid #e2e8f0;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th {
        background: #f7fafc;
        text-align: left;
        padding: 14px 16px;
        font-size: 13px;
        text-transform: uppercase;
        color: #4a5568;
        letter-spacing: 0.5px;
        white-space: nowrap;
        border-bottom: 2px solid #e2e8f0;
    }

    td {
        padding: 13px 16px;
        border-top: 1px solid #f7fafc;
        font-size: 14px;
    }

    tr:hover td {
        background: #f7fafc;
    }

    .data-col {
        max-width: 300px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        background: #f0fff4;
        color: #2f855a;
        border: 1px solid #c6f6d5;
    }

    .pagination {
        display: flex;
        justify-content: center;
        gap: 8px;
        padding: 25px 0 50px;
    }

    .pagination a,
    .pagination span {
        padding: 8px 14px;
        border-radius: 8px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s;
    }

    .pagination a {
        background: white;
        color: #2d3748;
        border: 1px solid #e2e8f0;
    }

    .pagination a:hover {
        border-color: #dc2626;
        color: #dc2626;
    }

    .pagination .active {
        background: #dc2626;
        color: white;
        border-color: #dc2626;
    }

    .empty {
        text-align: center;
        padding: 80px 20px;
        color: #a0aec0;
    }

    .empty i {
        font-size: 56px;
        margin-bottom: 16px;
    }

    .detail-btn {
        color: #38a169;
        cursor: pointer;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        transition: color 0.2s;
    }

    .detail-btn:hover {
        color: #dc2626;
        text-decoration: underline;
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1001;
        justify-content: center;
        align-items: center;
    }

    .modal-content {
        background: white;
        border-radius: 12px;
        padding: 35px;
        max-width: 480px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        position: relative;
    }

    .modal-content:before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #38a169 0%, #dc2626 100%);
        border-radius: 12px 12px 0 0;
    }

    .modal-content h3 {
        margin-bottom: 20px;
        font-size: 20px;
        font-weight: 600;
    }

    .modal-content .field {
        padding: 10px 0;
        border-bottom: 1px solid #f7fafc;
    }

    .modal-content .field:last-child {
        border-bottom: none;
    }

    .modal-content .field strong {
        display: block;
        font-size: 11px;
        color: #a0aec0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 2px;
    }

    .modal-content .field span {
        font-size: 15px;
        color: #2d3748;
    }

    .modal-close {
        position: absolute;
        top: 15px;
        right: 20px;
        font-size: 28px;
        cursor: pointer;
        color: #a0aec0;
        background: none;
        border: none;
        transition: color 0.2s;
    }

    .modal-close:hover {
        color: #dc2626;
    }

    @media (max-width: 768px) {
        .header .container {
            flex-wrap: wrap;
            gap: 10px;
        }
    }
    </style>
</head>
<body>

<div class="header">
    <div class="container">
        <div class="logo">
            <i class="fas fa-coins"></i>
            Prime<span>Money</span>
        </div>
        <div class="header-right">
            <?php if ($authenticated): ?>
                <span><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="?logout=1"><i class="fas fa-sign-out-alt"></i> Выйти</a>
            <?php endif; ?>
            <a href="index.html"><i class="fas fa-arrow-left"></i> На сайт</a>
        </div>
    </div>
</div>

<?php if ($authenticated): ?>

<div class="container">
    <div class="filters">
        <a href="admin.php" class="<?= $filter === '' ? 'active' : '' ?>">Все</a>
        <?php foreach ($types as $t): ?>
            <a href="admin.php?filter=<?= urlencode($t) ?>"
               class="<?= $filter === $t ? 'active' : '' ?>">
                <?= htmlspecialchars($labels[$t] ?? $t) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($total === 0): ?>
        <div class="empty">
            <i class="fas fa-inbox"></i>
            <p>Пока нет заявок</p>
        </div>
    <?php else: ?>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Тип</th>
                    <th>Данные</th>
                    <th>Дата</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row):
                    $dataArr = json_decode($row['data'], true);
                    $preview = '';

                    foreach (['name','phone','Имя','Телефон','fullName','Ваше имя'] as $f) {
                        if (!empty($dataArr[$f])) {
                            $preview = $dataArr[$f];
                            break;
                        }
                    }

                    if ($preview === '') {
                        $preview = mb_substr(json_encode($dataArr, JSON_UNESCAPED_UNICODE), 0, 50);
                    }

                    $escaped = htmlspecialchars(json_encode($dataArr, JSON_UNESCAPED_UNICODE), ENT_QUOTES);
                ?>
                    <tr>
                        <td>#<?= $row['id'] ?></td>
                        <td><span class="badge"><?= htmlspecialchars($labels[$row['form_type']] ?? $row['form_type']) ?></span></td>
                        <td class="data-col"><?= htmlspecialchars($preview) ?></td>
                        <td style="white-space:nowrap;"><?= $row['created_at'] ?></td>
                        <td><span class="detail-btn" onclick="showDetail('<?= $escaped ?>')">Подробнее</span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?><?= $filter ? '&filter='.urlencode($filter) : '' ?>"><i class="fas fa-chevron-left"></i></a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?><?= $filter ? '&filter='.urlencode($filter) : '' ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?><?= $filter ? '&filter='.urlencode($filter) : '' ?>"><i class="fas fa-chevron-right"></i></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="modal" id="detailModal">
    <div class="modal-content">
        <button class="modal-close" onclick="document.getElementById('detailModal').style.display='none'">&times;</button>
        <h3>Детали заявки</h3>
        <div id="detailBody"></div>
    </div>
</div>

<script>
const fieldLabels = {
    name: 'Имя',
    phone: 'Телефон',
    email: 'E-mail',
    comment: 'Комментарий',
    message: 'Сообщение',
    subject: 'Тема обращения',
    city: 'Город',
    service: 'Услуга',
    rating: 'Оценка',
    title: 'Заголовок отзыва',
    car_model: 'Марка и модель авто',
    year: 'Год выпуска',
    mileage: 'Пробег (км)',
    purpose: 'Цель займа',
    fullName: 'ФИО',
    amount: 'Желаемая сумма',
};

const valueLabels = {
    credit: 'Получение кредита',
    consult: 'Консультация',
    feedback: 'Обратная связь',
    cooperation: 'Сотрудничество',
    other: 'Другое',
    'auto-loan': 'Кредит под залог авто',
    business: 'Кредит для бизнеса',
    mortgage: 'Ипотека',
    consumer: 'Потребительский кредит',
    refinance: 'Рефинансирование',
    'real-estate': 'Кредит под залог недвижимости',
    'auto-credit': 'Автокредит',
    'credit-card': 'Кредитная карта',
    consultation: 'Консультация',
    repair: 'Ремонт автомобиля',
    treatment: 'Лечение',
    debt: 'Погашение долгов',
};

function translate(val) {
    return valueLabels[val] || val;
}

function showDetail(jsonStr) {
    const data = JSON.parse(jsonStr);
    const body = document.getElementById('detailBody');
    body.innerHTML = '';
    for (const [key, val] of Object.entries(data)) {
        const l = fieldLabels[key] || key;
        const div = document.createElement('div');
        div.className = 'field';
        div.innerHTML = `<strong>${l}</strong><span>${translate(val)}</span>`;
        body.appendChild(div);
    }
    document.getElementById('detailModal').style.display = 'flex';
}
window.onclick = function (e) {
    const m = document.getElementById('detailModal');
    if (e.target === m) m.style.display = 'none';
}
</script>

<?php else: ?>

<div class="login-wrap">
    <div class="login-box">
        <h2><i class="fas fa-shield-alt"></i> Админ-панель</h2>
        <p>PrimeMoney — авторизация</p>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="field">
                <label for="username">Имя пользователя</label>
                <input type="text" name="username" id="username" required>
            </div>
            <div class="field">
                <label for="password">Пароль</label>
                <input type="password" name="password" id="password" required>
            </div>
            <button type="submit" name="login" class="btn">Войти</button>
        </form>
    </div>
</div>

<?php endif; ?>

</body>
</html>
