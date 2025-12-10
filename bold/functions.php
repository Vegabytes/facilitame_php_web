<?php

use PHPMailer\PHPMailer\PHPMailer;

function pretty($txt)
{
    print("<pre>" . print_r($txt, true) . "</pre>");
}

function hello()
{
    $hour = intval(date("H")) + 2;
    if ($hour >= 6 && $hour < 12) {
        return "Buenos días";
    } elseif ($hour >= 12 && $hour < 20) {
        return "Buenas tardes";
    } else {
        return "Buenas noches";
    }
}

function validate_nif_cif_nie($id_number)
{
    $id_number = strtoupper($id_number);
    $num = [];

    for ($i = 0; $i < 9; $i++) {
        $num[$i] = substr($id_number, $i, 1);
    }

    if (!preg_match('/((^[A-Z]{1}[0-9]{7}[A-Z0-9]{1}$|^[T]{1}[A-Z0-9]{8}$)|^[0-9]{8}[A-Z]{1}$)/', $id_number)) {
        return 0;
    }

    // Standard NIFs
    if (preg_match('/(^[0-9]{8}[A-Z]{1}$)/', $id_number)) {
        return ($num[8] == substr('TRWAGMYFPDXBNJZSQVHLCKE', substr($id_number, 0, 8) % 23, 1)) ? 1 : -1;
    }

    // CIF algorithm
    $sum = $num[2] + $num[4] + $num[6];
    for ($i = 1; $i < 8; $i += 2) {
        $product = strval(2 * $num[$i]);
        $sum += substr($product, 0, 1) + (strlen($product) > 1 ? substr($product, 1, 1) : 0);
    }
    $n = 10 - substr($sum, strlen($sum) - 1, 1);

    // Special NIFs
    if (preg_match('/^[KLM]{1}/', $id_number)) {
        return ($num[8] == chr(64 + $n) || $num[8] == substr('TRWAGMYFPDXBNJZSQVHLCKE', substr($id_number, 1, 8) % 23, 1)) ? 1 : -1;
    }

    // CIFs
    if (preg_match('/^[ABCDEFGHJNPQRSUVW]{1}/', $id_number)) {
        return ($num[8] == chr(64 + $n) || $num[8] == substr($n, strlen($n) - 1, 1)) ? 2 : -2;
    }

    // NIEs
    if (preg_match('/^[XYZ]{1}/', $id_number)) {
        return ($num[8] == substr('TRWAGMYFPDXBNJZSQVHLCKE', substr(str_replace(['X', 'Y', 'Z'], ['0', '1', '2'], $id_number), 0, 8) % 23, 1)) ? 3 : -3;
    }

    return 0;
}

function controller($target = "")
{
    if ($target == "") $target = $_SERVER["REDIRECT_URL"];
    $target = str_replace('/api/', '/api-', $target);
    require CONTROLLER . $target . ".php";
    return json_encode($info);
}

function get_customers(mixed $user = "")
{
    global $pdo;

    if (comercial()) {
        $stmt = $pdo->prepare("SELECT id FROM sales_codes WHERE user_id = :user_id");
        $stmt->bindValue(":user_id", USER["id"]);
        $stmt->execute();
        $code_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($code_ids)) return [];

        $placeholders = implode(",", array_fill(0, count($code_ids), "?"));
        $stmt = $pdo->prepare("SELECT customer_id FROM customers_sales_codes WHERE sales_code_id IN ($placeholders)");
        $stmt->execute($code_ids);
        $customer_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($customer_ids)) return [];

        $placeholders = implode(",", array_fill(0, count($customer_ids), "?"));
        $stmt = $pdo->prepare("SELECT u.*, r.name AS role_name
            FROM users u 
            LEFT JOIN model_has_roles mhr ON mhr.model_id = u.id
            LEFT JOIN roles r ON mhr.role_id = r.id
            WHERE u.id IN ($placeholders)");
        $stmt->execute($customer_ids);
        return $stmt->fetchAll();

    } elseif (proveedor() && $user != "") {
        $category_ids = $user->getCategoryIds();
        if (empty($category_ids)) return [];

        $placeholders = implode(",", array_fill(0, count($category_ids), "?"));
        $stmt = $pdo->prepare("SELECT DISTINCT(user_id) FROM requests WHERE category_id IN ($placeholders)");
        $stmt->execute($category_ids);
        $customer_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($customer_ids)) return [];

        $placeholders = implode(",", array_fill(0, count($customer_ids), "?"));
        $stmt = $pdo->prepare("SELECT u.*, r.name AS role_name
            FROM users u 
            LEFT JOIN model_has_roles mhr ON mhr.model_id = u.id
            LEFT JOIN roles r ON mhr.role_id = r.id
            WHERE u.id IN ($placeholders) ORDER BY u.lastname ASC, u.name ASC");
        $stmt->execute($customer_ids);
        return $stmt->fetchAll();

    } elseif (admin()) {
        $stmt = $pdo->prepare("SELECT users.*, rol.name AS role_name, 
            (SELECT COUNT(*) FROM requests WHERE requests.user_id = users.id) AS services_number
            FROM users
            JOIN model_has_roles mhr ON mhr.model_id = users.id
            JOIN roles rol ON rol.id = mhr.role_id
            WHERE mhr.role_id IN (4,5,6)
            ORDER BY users.created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll();

    } elseif (asesoria()) {
        $stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = :user_id");
        $stmt->bindValue(":user_id", USER["id"]);
        $stmt->execute();
        $advisory = $stmt->fetch();

        if (!$advisory) return [];

        $stmt = $pdo->prepare("SELECT u.*, r.name AS role_name,
            (SELECT COUNT(*) FROM requests WHERE requests.user_id = u.id) AS services_number
            FROM users u
            INNER JOIN customers_advisories ca ON ca.customer_id = u.id
            LEFT JOIN model_has_roles mhr ON mhr.model_id = u.id
            LEFT JOIN roles r ON r.id = mhr.role_id
            WHERE ca.advisory_id = :advisory_id
            ORDER BY u.created_at DESC");
        $stmt->bindValue(":advisory_id", $advisory["id"]);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    return [];
}

function customer_belongs_to_user($customer_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE deleted = 0 AND user_id = :user_id AND id = :customer_id");
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->bindValue(":customer_id", $customer_id);
    $stmt->execute();
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    return $res ?: false;
}

function get_quotations()
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE user_id = :user_id AND deleted = 0 ORDER BY id ASC");
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($customers)) return [];

    $placeholders = implode(",", array_fill(0, count($customers), "?"));
    $stmt = $pdo->prepare("SELECT * FROM quotations WHERE customer_id IN ($placeholders)");
    $stmt->execute($customers);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function string_ids($array)
{
    return implode(",", array_column($array, "id"));
}

function get_quotation_items($quotation_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id = :quotation_id");
    $stmt->bindValue(":quotation_id", $quotation_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function json_response($status, $message, $code, $data = [], $icon = "")
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        "status" => $status,
        "message_html" => $message,
        "message_plain" => strip_tags($message),
        "code" => $code,
        "data" => $data,
        "icon" => $icon
    ]);
    exit;
}

function send_mail($to_address, $to_name, $subject, $body, $mid, $attachments = [], $from = SMTP_FROM)
{
    $mail = new PHPMailer();
    $mail->isSMTP();
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = (SMTP_PORT == 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PORT;

    $mail->setFrom(SMTP_USERNAME, $from);
    
    if (ENVIRONMENT === "DEMO") {
        $mail->addAddress("erlantz@facilitame.es", $to_name);
        $subject = "DEMO: " . $subject;
    } else {
        $mail->addAddress($to_address, $to_name);
    }
    
    $mail->Subject = $subject;
    $mail->Body = $body . "<p style='font-size:5px;margin-top:2rem;'>MID:" . $mid . "</p>";

    foreach ($attachments as $attachment) {
        $mail->addAttachment($attachment);
    }

    return $mail->send();
}

function toastr()
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION["toastr"])) return;

    $type = $_SESSION["toastr"]["type"];
    $message = htmlspecialchars($_SESSION["toastr"]["message_html"]);
    unset($_SESSION["toastr"]);
    
    echo "<script>
        toastr.options = {
            'closeButton': true, 'debug': false, 'newestOnTop': false, 'progressBar': true,
            'positionClass': 'toastr-top-center', 'preventDuplicates': false, 'onclick': null,
            'showDuration': '300', 'hideDuration': '1000', 'timeOut': '10000', 'extendedTimeOut': '10000',
            'showEasing': 'swing', 'hideEasing': 'linear', 'showMethod': 'fadeIn', 'hideMethod': 'fadeOut'
        };
        toastr." . ($type == "success" ? "success" : "warning") . "('{$message}', '');
    </script>";
}

function set_toastr($status, $message_html)
{
    $_SESSION["toastr"] = [
        "status" => $status,
        "type" => $status == "ok" ? "success" : "warning",
        "message_html" => $message_html
    ];
}

function secho($txt)
{
    echo htmlspecialchars($txt, ENT_QUOTES, "UTF-8");
}

function display_role($role = null)
{
    $role = $role ?? USER["role"];
    $roles = [
        'autonomo' => "Autónomo",
        'particular' => "Particular",
        'empresa' => "Empresa",
        'proveedor' => "Proveedor",
        'administrador' => "Administrador",
        'comercial' => "Equipo comercial",
        'asesoria' => "Asesoría"
    ];
    return $roles[$role] ?? "";
}

function get_user_services()
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT req.*, cat.name AS category_name, sta.status_name AS status
        FROM requests req
        LEFT JOIN categories cat ON cat.id = req.category_id
        LEFT JOIN requests_statuses sta ON sta.id = req.status_id
        WHERE req.user_id = :user_id AND req.deleted_at IS NULL 
        ORDER BY req.created_at DESC");
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->execute();
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($services as $i => $service) {
        $services[$i]["request_info"] = get_request_category_info($service);
    }
    return $services;
}

function username()
{
    return USER["name"] . " " . USER["lastname"];
}

function user_name($user)
{
    return $user["name"] . " " . $user["lastname"];
}

function phone($phone_no)
{
    return chunk_split(str_replace(" ", "", $phone_no), 3, " ");
}

function fdate($date_string)
{
    return date("d/m/Y", strtotime($date_string));
}

function fdatetime($date_string)
{
    return date("d/m/Y H:i", strtotime($date_string));
}

function get_services()
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM categories WHERE parent_id IS NULL AND deleted_at IS NULL ORDER BY list_order ASC");
    $stmt->execute();
    $categories = $stmt->fetchAll();

    foreach ($categories as $i => $cat) {
        $categories[$i]["form_elements"] = [];
        $categories[$i]["subcategories"] = [];

        // Form elements
        $stmt = $pdo->prepare("SELECT * FROM form_categories WHERE category_id = :category_id AND (for_user IS NULL OR for_user = :for_user) ORDER BY name ASC");
        $stmt->bindValue(":category_id", $cat["id"]);
        $stmt->bindValue(":for_user", USER["role"]);
        $stmt->execute();
        $form_elements = $stmt->fetchAll();
        if (!empty($form_elements)) $categories[$i]["form_elements"] = $form_elements;

        // Subcategories
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE deleted_at IS NULL AND parent_id = :parent_id ORDER BY list_order ASC, name ASC");
        $stmt->bindValue(":parent_id", $cat["id"]);
        $stmt->execute();
        $subcategories = $stmt->fetchAll();

        foreach ($subcategories as $j => $sub) {
            $categories[$i]["subcategories"][] = $sub;

            $stmt = $pdo->prepare("SELECT * FROM form_categories WHERE category_id = :category_id AND (for_user IS NULL OR for_user = :for_user) ORDER BY name ASC");
            $stmt->bindValue(":category_id", $sub["id"]);
            $stmt->bindValue(":for_user", USER["role"]);
            $stmt->execute();
            $form_elements = $stmt->fetchAll();
            if (!empty($form_elements)) $categories[$i]["subcategories"][$j]["form_elements"] = $form_elements;
        }
    }

    // Filter by sales rep excluded categories
    $customer_sales_rep = customer_get_sales_rep(USER["id"]);
    if ($customer_sales_rep) {
        $excluded_services = get_excluded_services($customer_sales_rep);
        $categories = array_values(array_filter($categories, fn($service) => !in_array($service["id"], $excluded_services)));
    }

    return $categories;
}

function get_service_form($service_id)
{
    global $pdo;
    if (empty($service_id)) return false;

    $stmt = $pdo->prepare("SELECT * FROM form_categories WHERE category_id = :service_id AND (for_user IS NULL OR for_user = :for_user) ORDER BY id ASC");
    $stmt->bindValue(":service_id", $service_id);
    $stmt->bindValue(":for_user", USER["role"]);
    $stmt->execute();
    $form_elements = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = :service_id");
    $stmt->bindValue(":service_id", $service_id);
    $stmt->execute();
    $service_info = $stmt->fetch();

    if (guest()) $service_info["phone"] = "";

    return ["form_elements" => $form_elements, "service_info" => $service_info];
}

function get_request($request_id)
{
    global $pdo;
    $request = false;

    if (admin()) {
        $stmt = $pdo->prepare("SELECT * FROM requests WHERE id = :request_id");
        $stmt->bindValue(":request_id", $request_id);
        $stmt->execute();
        $request = $stmt->fetchAll();

    } elseif (proveedor()) {
        // Validar que el proveedor tiene categorías asignadas
        $categories = USER["categories"] ?? '';
        if (empty($categories)) {
            return false;
        }
        // Sanitizar categorías (solo números y comas)
        $categories = preg_replace('/[^0-9,]/', '', $categories);
        if (empty($categories)) {
            return false;
        }
        $stmt = $pdo->prepare("SELECT * FROM requests WHERE id = :request_id AND category_id IN (" . $categories . ")");
        $stmt->bindValue(":request_id", $request_id);
        $stmt->execute();
        $request = $stmt->fetchAll();

    } elseif (cliente()) {
        $stmt = $pdo->prepare("SELECT rs.status_name, req.* FROM requests req 
            JOIN requests_statuses rs ON rs.id = req.status_id
            WHERE req.id = :request_id AND req.user_id = :user_id");
        $stmt->bindValue(":request_id", $request_id);
        $stmt->bindValue(":user_id", USER["id"]);
        $stmt->execute();
        $request = $stmt->fetchAll();

        foreach ($request as $i => $req) {
            $request[$i]["created_at_display"] = date("d/m/Y", strtotime($req["created_at"]));
            $request[$i]["updated_at_display"] = !is_null($req["updated_at"]) ? date("d/m/Y", strtotime($req["updated_at"])) : "";
        }

    } elseif (comercial() && user_can_access_request($request_id)) {
        $stmt = $pdo->prepare("SELECT * FROM requests WHERE id = :request_id");
        $stmt->bindValue(":request_id", $request_id);
        $stmt->execute();
        $request = $stmt->fetchAll();

    } elseif (asesoria()) {
        $stmt = $pdo->prepare("SELECT id FROM advisories WHERE user_id = ?");
        $stmt->execute([USER['id']]);
        $advisory_row = $stmt->fetch();
        
        if ($advisory_row) {
            $stmt = $pdo->prepare("SELECT req.* FROM requests req 
                INNER JOIN customers_advisories ca ON ca.customer_id = req.user_id
                WHERE req.id = :request_id AND ca.advisory_id = :advisory_id");
            $stmt->bindValue(":request_id", $request_id);
            $stmt->bindValue(":advisory_id", $advisory_row['id']);
            $stmt->execute();
            $request = $stmt->fetchAll();
        }
    }

    return ($request !== false && count($request) === 1) ? $request[0] : false;
}

function get_category($category_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = :category_id");
    $stmt->bindValue(":category_id", $category_id);
    $stmt->execute();
    $category = $stmt->fetchAll();

    if (count($category) !== 1) return false;

    if (guest()) $category[0]["phone"] = "";
    return $category[0];
}

function print_request_status($request_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT status_name FROM requests_statuses rs, requests req WHERE req.id = :request_id AND rs.id = req.status_id");
    $stmt->bindValue(":request_id", $request_id);
    $stmt->execute();
    $status = $stmt->fetch();
    echo $status ? get_badge_html($status["status_name"]) : "";
}

function print_offer_status($offer_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT status_name FROM requests_statuses rs, offers of WHERE of.id = :offer_id AND rs.id = of.status_id");
    $stmt->bindValue(":offer_id", $offer_id);
    $stmt->execute();
    $status = $stmt->fetch();
    echo $status ? get_badge_html($status["status_name"]) : "";
}

function get_badge_html($status)
{
    $key = is_numeric($status) ? (int)$status : strtolower(trim($status));
    
    $badges = [
        'iniciado' => ['dark', 'Iniciada'], 1 => ['dark', 'Iniciada'],
        'oferta disponible' => ['warning', 'Oferta disponible'], 2 => ['warning', 'Oferta disponible'],
        'aceptada' => ['info', 'Aceptada'], 3 => ['info', 'Aceptada'],
        'en curso' => ['info', 'En curso'], 4 => ['info', 'En curso'],
        'rechazada' => ['danger', 'Rechazada'], 5 => ['danger', 'Rechazada'],
        'llamada sin respuesta' => ['danger', 'Sin respuesta'], 6 => ['danger', 'Sin respuesta'],
        'activada' => ['success', 'Activada'], 7 => ['success', 'Activada'],
        'revisión solicitada' => ['danger', 'Revisión solicitada'], 8 => ['danger', 'Revisión solicitada'],
        'eliminada' => ['dark', 'Eliminada'], 9 => ['dark', 'Eliminada'],
        'aplazada' => ['warning', 'Aplazada'], 10 => ['warning', 'Aplazada'],
        'desactivada' => ['neutral', 'Desactivada'], 11 => ['neutral', 'Desactivada'],
    ];
    
    if (isset($badges[$key])) {
        return '<span class="badge badge-' . $badges[$key][0] . '">' . $badges[$key][1] . '</span>';
    }
    return '<span class="badge badge-neutral">' . ucfirst($status) . '</span>';
}

function get_badge_html_incidents($status, $status_id = null)
{
    $key = $status_id ?? strtolower(trim($status));
    
    $badges = [
        1 => ['dark', 'Abierta'], 'abierta' => ['dark', 'Abierta'],
        2 => ['info', 'En curso'], 'gestionando' => ['info', 'En curso'],
        3 => ['success', 'Validada'], 'validada' => ['success', 'Validada'],
        10 => ['danger', 'Cerrada'], 'cerrada' => ['danger', 'Cerrada'],
    ];
    
    if (isset($badges[$key])) {
        return '<span class="badge badge-' . $badges[$key][0] . '">' . $badges[$key][1] . '</span>';
    }
    return '<span class="badge badge-neutral">Desconocido</span>';
}

function get_messages($request_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM messages_v2 WHERE request_id = :request_id ORDER BY created_at ASC");
    $stmt->bindValue(":request_id", $request_id);
    $stmt->execute();
    return $stmt->fetchAll();
}

function build_messages($messages, $request_id)
{
    $html = "";
    $requestor = get_requestor($request_id);

    foreach ($messages as $message) {
        if (!admin() && !comercial()) {
            $html .= ($message["user_id"] == USER["id"]) 
                ? build_out_message($message, $requestor) 
                : build_in_message($message, $requestor);
        } else {
            $html .= ($message["user_id"] != $requestor["id"]) 
                ? build_out_message($message, $requestor) 
                : build_in_message($message, $requestor);
        }
    }
    return $html;
}

function build_out_message($message, $requestor)
{
    $datetime = fdatetime($message["created_at"]);
    $name = htmlspecialchars(cliente() ? $requestor["name"] : "Facilítame", ENT_QUOTES, "UTF-8");
    $content = htmlspecialchars($message["content"], ENT_QUOTES, "UTF-8");
    
    return <<<HTML
    <div class="d-flex justify-content-end mb-10">
        <div class="d-flex flex-column align-items-end">
            <div class="d-flex align-items-center mb-2">
                <div class="me-3">
                    <span class="text-muted fs-7 mb-1">{$datetime}</span>
                    <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary ms-1">{$name}</a>
                </div>
            </div>
            <div class="p-5 rounded bg-light-primary text-gray-900 fw-semibold mw-lg-400px text-end" data-kt-element="message-text">{$content}</div>
        </div>
    </div>
HTML;
}

function build_in_message($message, $requestor)
{
    $datetime = fdatetime($message["created_at"]);
    $name = htmlspecialchars(cliente() ? "Facilítame" : $requestor["name"], ENT_QUOTES, "UTF-8");
    $content = htmlspecialchars($message["content"], ENT_QUOTES, "UTF-8");
    
    return <<<HTML
    <div class="d-flex justify-content-start mb-10">
        <div class="d-flex flex-column align-items-start">
            <div class="d-flex align-items-center mb-2">
                <div class="ms-3">
                    <a href="#" class="fs-5 fw-bold text-gray-900 text-hover-primary me-1">{$name}</a>
                    <span class="text-muted fs-7 mb-1">{$datetime}</span>
                </div>
            </div>
            <div class="p-5 rounded bg-light-info text-gray-900 fw-semibold mw-lg-400px text-start chat-message" data-kt-element="message-text">{$content}</div>
        </div>
    </div>
HTML;
}

function user_can_access_request($request_id)
{
    global $pdo;

    if (admin()) return true;

    if (cliente()) {
        $stmt = $pdo->prepare("SELECT 1 FROM requests WHERE user_id = :user_id AND id = :request_id");
        $stmt->bindValue(":user_id", USER["id"]);
        $stmt->bindValue(":request_id", $request_id);
        $stmt->execute();
        return $stmt->fetch() !== false;

    } elseif (proveedor()) {
        // Validar que el proveedor tiene categorías asignadas
        $categories = USER["categories"] ?? '';
        if (empty($categories)) {
            return false;
        }
        // Sanitizar categorías (solo números y comas)
        $categories = preg_replace('/[^0-9,]/', '', $categories);
        if (empty($categories)) {
            return false;
        }
        $stmt = $pdo->prepare("SELECT 1 FROM requests WHERE id = :request_id AND category_id IN (" . $categories . ")");
        $stmt->bindValue(":request_id", $request_id);
        $stmt->execute();
        return $stmt->fetch() !== false;

    } elseif (comercial()) {
        return in_array($request_id, rep_get_request_ids());
    }

    return false;
}

function message_belongs_to_user($message_id)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT request_id FROM messages_v2 WHERE user_id = :user_id AND id = :message_id");
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->bindValue(":message_id", $message_id);
    $stmt->execute();
    $message = $stmt->fetch();

    if (!$message) return false;

    $stmt = $pdo->prepare("SELECT 1 FROM requests WHERE user_id = :user_id AND id = :request_id");
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->bindValue(":request_id", $message["request_id"]);
    $stmt->execute();
    return $stmt->fetch() !== false;
}

function get_documents($request_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM request_files WHERE request_id = :request_id ORDER BY created_at DESC");
    $stmt->bindValue(":request_id", $request_id);
    $stmt->execute();
    $documents = $stmt->fetchAll();

    if (IS_MOBILE_APP && !empty($documents)) {
        // Get file types once, outside the loop
        $stmt = $pdo->prepare("SELECT id, name FROM file_types WHERE deleted_at IS NULL");
        $stmt->execute();
        $file_types = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        foreach ($documents as &$doc) {
            $doc["file_type_display"] = $file_types[$doc["file_type_id"]] ?? '';
            $doc["created_at_display"] = date("d/m/Y", strtotime($doc["created_at"]));
        }
        unset($doc);
    }
    return $documents;
}

function is_image($mime_type)
{
    return is_null($mime_type) || strpos($mime_type, "image") !== false;
}

function get_file_types()
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM file_types WHERE id > 0 AND deleted_at IS NULL ORDER BY name ASC");
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_file_types_kp()
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, name FROM file_types WHERE deleted_at IS NULL ORDER BY name ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

function get_offers($request_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT of.*, rs.status_name
        FROM offers of
        LEFT JOIN requests_statuses rs ON rs.id = of.status_id
        WHERE of.request_id = :request_id
        AND (of.deleted_at IS NULL OR (of.deleted_at IS NOT NULL AND of.status_id = 11))
        ORDER BY of.created_at DESC");
    $stmt->bindValue(":request_id", $request_id);
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_request_category_info($request)
{
    $keys = []; // FIX: Initialize $keys array
    
    $category_keys = [
        17 => ["alarm_location", "city_name"],
        15 => ["where_cuota"],
        3 => ["car_model", "car_plate"],
        2 => ["home_address"],
        9 => ["other"],
        4 => ["address_risk"],
        16 => ["phone_operator", "address"],
        18 => ["vehicle_type_interest"],
        30 => ["activity_type"],
        26 => ["goal"], 23 => ["goal"], 29 => ["goal"], 22 => ["goal"],
        28 => ["goal"], 24 => ["for_services"], 27 => ["goal"], 21 => ["goal"], 25 => ["goal"],
        44 => ["business_type"],
        42 => ["service_needed_if_yes", "service_needed_if_no"],
        45 => ["business_type"],
        41 => ["main_services_needed"],
        43 => ["business_type_if_yes"],
        37 => ["business_activity"], 34 => ["business_activity"], 38 => ["business_activity"],
        33 => ["business_activity"], 35 => ["business_activity"], 32 => ["business_activity"],
        39 => ["business_activity"], 36 => ["business_activity"],
        47 => ["image_type_needed"],
        48 => ["video_type_needed"],
        49 => ["reform_type_needed"],
        56 => ["business_type", "olfactory_goal"],
        57 => ["pest_type", "service_location"],
    ];

    $keys = $category_keys[$request["category_id"]] ?? [];
    if (empty($keys)) return "";

    if (is_null($request["form_values"])) return "";

    $form_values = json_decode($request["form_values"], true);
    if (!is_array($form_values)) {
        $form_values = json_decode($form_values, true);
    }
    if (!is_array($form_values)) return "";

    $result = [];
    foreach ($form_values as $fv) {
        if (isset($fv["key"]) && in_array($fv["key"], $keys)) {
            $result[] = ($fv["key"] == "other") ? substr($fv["value"], 0, 20) . "..." : $fv["value"];
        }
    }
    return implode(" | ", $result);
}

function get_requests()
{
    global $pdo;

    if (proveedor()) {
        // Validar que el proveedor tiene categorías asignadas
        $categories = USER["categories"] ?? '';
        if (empty($categories)) {
            return [];
        }
        // Sanitizar categorías (solo números y comas)
        $categories = preg_replace('/[^0-9,]/', '', $categories);
        if (empty($categories)) {
            return [];
        }
        $stmt = $pdo->prepare("SELECT CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) AS customer_full_name,
            req.*, cat.name AS category_name, sta.status_name AS status
            FROM requests req
            LEFT JOIN categories cat ON cat.id = req.category_id
            LEFT JOIN requests_statuses sta ON sta.id = req.status_id
            LEFT JOIN users u ON u.id = req.user_id
            WHERE req.category_id IN (" . $categories . ")
            ORDER BY req.request_date DESC");
        $stmt->execute();
        $requests = $stmt->fetchAll();

    } elseif (admin()) {
        $stmt = $pdo->prepare("SELECT CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) AS customer_full_name,
            req.*, cat.name AS category_name, sta.status_name AS status
            FROM requests req
            LEFT JOIN categories cat ON cat.id = req.category_id
            LEFT JOIN requests_statuses sta ON sta.id = req.status_id
            LEFT JOIN users u ON u.id = req.user_id
            ORDER BY req.request_date DESC");
        $stmt->execute();
        $requests = $stmt->fetchAll();

    } elseif (comercial()) {
        $stmt = $pdo->prepare("SELECT id FROM sales_codes WHERE user_id = :user_id");
        $stmt->bindValue(":user_id", USER["id"]);
        $stmt->execute();
        $code_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($code_ids)) return [];

        $placeholders = implode(",", array_fill(0, count($code_ids), "?"));
        $stmt = $pdo->prepare("SELECT customer_id FROM customers_sales_codes WHERE sales_code_id IN ($placeholders)");
        $stmt->execute($code_ids);
        $customer_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($customer_ids)) return [];

        $placeholders = implode(",", array_fill(0, count($customer_ids), "?"));
        $stmt = $pdo->prepare("SELECT CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) AS customer_full_name,
            req.*, cat.name AS category_name, sta.status_name AS status
            FROM requests req
            LEFT JOIN categories cat ON cat.id = req.category_id
            LEFT JOIN requests_statuses sta ON sta.id = req.status_id
            LEFT JOIN users u ON u.id = req.user_id
            WHERE req.user_id IN ($placeholders) 
            ORDER BY req.request_date DESC");
        $stmt->execute($customer_ids);
        $requests = $stmt->fetchAll();

    } elseif (cliente()) {
        $stmt = $pdo->prepare("SELECT req.*, cat.name AS category_name, sta.status_name AS status
            FROM requests req
            LEFT JOIN categories cat ON cat.id = req.category_id
            LEFT JOIN requests_statuses sta ON sta.id = req.status_id
            WHERE req.user_id = :user_id 
            ORDER BY req.request_date DESC");
        $stmt->bindValue(":user_id", USER["id"]);
        $stmt->execute();
        $requests = $stmt->fetchAll();

        foreach ($requests as $i => $request) {
            $requests[$i]["created_at_display"] = date("d/m/Y", strtotime($request["created_at"]));
            $requests[$i]["updated_at_display"] = !is_null($request["updated_at"]) ? date("d/m/Y", strtotime($request["updated_at"])) : "";
            $requests[$i]["request_info"] = get_request_category_info($request);
            if (IS_MOBILE_APP) {
                $requests[$i]["pending_notifications"] = count(get_request_customer_notifications($request["id"]));
            }
        }
        return $requests;
    } else {
        return [];
    }

    foreach ($requests as $i => $request) {
        $requests[$i]["request_info"] = get_request_category_info($request);
    }
    return $requests;
}

function get_provider_categories($user_id, $user_role)
{
    if ($user_role !== "proveedor") return "";

    global $pdo;
    $stmt = $pdo->prepare("SELECT category_id FROM provider_categories WHERE provider_id = :user_id");
    $stmt->bindValue(":user_id", $user_id);
    $stmt->execute();
    return implode(", ", $stmt->fetchAll(PDO::FETCH_COLUMN));
}

// Role check functions
function proveedor() { return USER["role"] === "proveedor"; }
function comercial() { return USER["role"] === "comercial"; }
function asesoria() { return USER["role"] === "asesoria"; }
function sales_rep() { return comercial(); }
function particular() { return USER["role"] === "particular"; }
function autonomo() { return USER["role"] === "autonomo"; }
function empresa() { return USER["role"] === "empresa"; }
function cliente() { return in_array(USER["role"], ["particular", "autonomo", "empresa"]); }
function admin() { return USER["role"] === "administrador"; }
function guest($user = "") {
    return defined("USER") ? (USER["id"] == "361") : ($user["id"] == "361");
}

function get_requestor($request_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT u.id, u.name, u.lastname, u.phone, u.email, u.nif_cif
        FROM users u
        LEFT JOIN requests r ON r.user_id = u.id
        WHERE r.id = :request_id");
    $stmt->bindValue(":request_id", $request_id);
    $stmt->execute();
    return $stmt->fetch();
}

function get_request_status($request_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT status_id FROM requests WHERE id = :request_id");
    $stmt->bindValue(":request_id", $request_id);
    $stmt->execute();
    $status = $stmt->fetch();
    return $status ? $status["status_id"] : false;
}

function offer_belongs_to_provider($offer_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT 1 FROM offers WHERE id = :offer_id AND provider_id = :provider_id");
    $stmt->bindValue(":offer_id", $offer_id);
    $stmt->bindValue(":provider_id", USER["id"]);
    $stmt->execute();
    return $stmt->fetch() !== false;
}

function get_rescheduled_requests()
{
    global $pdo;

    if (proveedor()) {
        // Validar que el proveedor tiene categorías asignadas
        $categories = USER["categories"] ?? '';
        if (empty($categories)) {
            return [];
        }
        // Sanitizar categorías (solo números y comas)
        $categories = preg_replace('/[^0-9,]/', '', $categories);
        if (empty($categories)) {
            return [];
        }
        $stmt = $pdo->prepare("SELECT CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) AS customer_full_name,
            req.*, cat.name AS category_name, sta.status_name AS status
            FROM requests req
            LEFT JOIN categories cat ON cat.id = req.category_id
            LEFT JOIN requests_statuses sta ON sta.id = req.status_id
            LEFT JOIN users u ON u.id = req.user_id
            WHERE req.status_id = 10 AND req.category_id IN (" . $categories . ")
            ORDER BY req.request_date DESC");
        $stmt->execute();

    } elseif (admin()) {
        $stmt = $pdo->prepare("SELECT CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) AS customer_full_name,
            req.*, cat.name AS category_name, sta.status_name AS status
            FROM requests req
            LEFT JOIN categories cat ON cat.id = req.category_id
            LEFT JOIN requests_statuses sta ON sta.id = req.status_id
            LEFT JOIN users u ON u.id = req.user_id
            WHERE req.status_id = 10
            ORDER BY req.request_date DESC");
        $stmt->execute();

    } elseif (comercial()) {
        $stmt = $pdo->prepare("SELECT id FROM sales_codes WHERE user_id = :user_id");
        $stmt->bindValue(":user_id", USER["id"]);
        $stmt->execute();
        $code_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (empty($code_ids)) return [];

        $placeholders = implode(",", array_fill(0, count($code_ids), "?"));
        $stmt = $pdo->prepare("SELECT customer_id FROM customers_sales_codes WHERE sales_code_id IN ($placeholders)");
        $stmt->execute($code_ids);
        $customer_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (empty($customer_ids)) return [];

        $placeholders = implode(",", array_fill(0, count($customer_ids), "?"));
        $stmt = $pdo->prepare("SELECT CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.lastname, '')) AS customer_full_name,
            req.*, cat.name AS category_name, sta.status_name AS status
            FROM requests req
            LEFT JOIN categories cat ON cat.id = req.category_id
            LEFT JOIN requests_statuses sta ON sta.id = req.status_id
            LEFT JOIN users u ON u.id = req.user_id
            WHERE req.status_id = 10 AND req.user_id IN ($placeholders)
            ORDER BY req.request_date DESC");
        $stmt->execute($customer_ids);

    } elseif (cliente()) {
        $stmt = $pdo->prepare("SELECT req.*, cat.name AS category_name, sta.status_name AS status
            FROM requests req
            LEFT JOIN categories cat ON cat.id = req.category_id
            LEFT JOIN requests_statuses sta ON sta.id = req.status_id
            WHERE req.status_id = 10 AND req.user_id = :user_id
            ORDER BY req.request_date DESC");
        $stmt->bindValue(":user_id", USER["id"]);
        $stmt->execute();
    } else {
        return [];
    }

    $requests = $stmt->fetchAll();
    foreach ($requests as $i => $request) {
        $requests[$i]["request_info"] = get_request_category_info($request);
        if (cliente()) {
            $requests[$i]["created_at_display"] = date("d/m/Y", strtotime($request["created_at"]));
            $requests[$i]["updated_at_display"] = !is_null($request["updated_at"]) ? date("d/m/Y", strtotime($request["updated_at"])) : "";
        }
    }
    return $requests;
}

function get_available_offers($request_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM offers WHERE deleted_at IS NULL AND status_id = 2 AND request_id = :request_id");
    $stmt->bindValue(":request_id", $request_id);
    $stmt->execute();
    return (int)$stmt->fetchColumn();
}

function get_comments($request_id)
{
    if (!proveedor() && !admin() && !comercial()) return "";

    global $pdo;
    $stmt = $pdo->prepare("SELECT comments FROM provider_comments WHERE request_id = :request_id");
    $stmt->bindValue(":request_id", $request_id);
    $stmt->execute();
    $comments = $stmt->fetch();
    return $comments ? html_entity_decode($comments["comments"]) : "";
}

function get_commissions()
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM commissions WHERE deleted_at IS NULL");
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_commissions_kp()
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, display FROM commissions WHERE deleted_at IS NULL");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

function rep_get_request_ids()
{
    global $pdo;
    if (!comercial()) return [];

    $stmt = $pdo->prepare("SELECT id FROM sales_codes WHERE user_id = :user_id");
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->execute();
    $code_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($code_ids)) return [];

    $placeholders = implode(",", array_fill(0, count($code_ids), "?"));
    $stmt = $pdo->prepare("SELECT customer_id FROM customers_sales_codes WHERE sales_code_id IN ($placeholders)");
    $stmt->execute($code_ids);
    $customer_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($customer_ids)) return [];

    $placeholders = implode(",", array_fill(0, count($customer_ids), "?"));
    $stmt = $pdo->prepare("SELECT id FROM requests WHERE user_id IN ($placeholders) ORDER BY request_date DESC");
    $stmt->execute($customer_ids);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function check_sales_code($sales_code)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM sales_codes WHERE expires_at IS NULL AND deleted_at IS NULL AND code = :sales_code");
    $stmt->bindValue(":sales_code", $sales_code);
    $stmt->execute();
    $result = $stmt->fetch();
    return $result ? $result["id"] : false;
}

function notification($sender_id, $receiver_id, $request_id, $title, $description)
{
    global $pdo;

    // Determinar user_id: si hay request_id, obtenerlo de la solicitud; si no, usar receiver_id
    $user_id = null;
    if ($request_id) {
        $stmt = $pdo->prepare("SELECT user_id FROM requests WHERE id = ?");
        $stmt->execute([$request_id]);
        $req = $stmt->fetch();
        $user_id = $req ? $req['user_id'] : $receiver_id;
    } else {
        // Sin request_id (ej: facturas), el destinatario es el user_id
        $user_id = $receiver_id;
    }

    $stmt = $pdo->prepare("INSERT INTO notifications SET sender_id = :sender_id, receiver_id = :receiver_id, user_id = :user_id, request_id = :request_id, title = :title, description = :description");
    $stmt->bindValue(":sender_id", $sender_id);
    $stmt->bindValue(":receiver_id", $receiver_id);
    $stmt->bindValue(":user_id", $user_id);
    $stmt->bindValue(":request_id", $request_id);
    $stmt->bindValue(":title", $title);
    $stmt->bindValue(":description", $description);
    $stmt->execute();

    $user = get_user($receiver_id);
    if (empty($user) || !isset($user["email"])) {
        error_log("notification(): Usuario receptor no encontrado (ID: $receiver_id)");
        return false;
    }

    $to_email = !empty($user["email_notifications"]) ? $user["email_notifications"] : $user["email"];
    $body = "<p style='font-size:1.2rem'><b>Hola " . ucwords($user["name"]) . ",</b></p><br>
        <p>{$description}</p>
        <p><a href='" . ROOT_URL . "'><b>Accede a Facilítame</b></a> para consultar los detalles</p><br>
        <p><b>¡Te esperamos!</b><br>El Equipo de Facilítame</p>";
    
    send_mail($to_email, ucwords($user["name"]), $title, $body, 1487865435);
}

function notification_v2($sender_id, $receiver_id, $request_id, $title, $description, $email_subject, $email_template, $data = [])
{
    try {
        global $pdo;

        // Determinar user_id
        $user_id = null;
        if ($request_id) {
            $stmt = $pdo->prepare("SELECT user_id FROM requests WHERE id = ?");
            $stmt->execute([$request_id]);
            $req = $stmt->fetch();
            $user_id = $req ? $req['user_id'] : $receiver_id;
        } else {
            $user_id = $receiver_id;
        }

        $stmt = $pdo->prepare("INSERT INTO notifications SET sender_id = :sender_id, receiver_id = :receiver_id, user_id = :user_id, request_id = :request_id, title = :title, description = :description");
        $stmt->bindValue(":sender_id", $sender_id);
        $stmt->bindValue(":receiver_id", $receiver_id);
        $stmt->bindValue(":user_id", $user_id);
        $stmt->bindValue(":request_id", $request_id);
        $stmt->bindValue(":title", $title);
        $stmt->bindValue(":description", $description);
        $stmt->execute();

        $user = get_user($receiver_id);
        $to_email = !empty($user["email_notifications"]) ? $user["email_notifications"] : $user["email"];

        ob_start();
        require(EMAIL_TEMPLATES_DIR . "/" . $email_template . ".php");
        $email_body = ob_get_clean();

        send_mail($to_email, ucwords($user["name"]), $email_subject, $email_body, $mid);

        $stmt = $pdo->prepare("SELECT firebase_token, platform FROM users WHERE id = :user_id");
        $stmt->bindValue(":user_id", $receiver_id);
        $stmt->execute();
        $user_notification_info = $stmt->fetch();

        if ($user_notification_info) {
            send_notification($user_notification_info, $email_subject, "Entra en Facilítame para ver los detalles", $request_id);
        }
    } catch (Throwable $e) {
        file_put_contents(ROOT_DIR . "/push-notifications.log", date("d/m/Y H:i:s") . " : " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
    }
}

function send_notification($user_notification_info, $notification_subject, $notification_body, $request_id)
{
    if (!$user_notification_info || empty($user_notification_info["firebase_token"]) || empty($user_notification_info["platform"])) {
        return;
    }

    if ($user_notification_info["platform"] == "ios") {
        sendApnsNotification($user_notification_info["firebase_token"], $notification_subject, $notification_body, $request_id);
    } else {
        sendFcmNotification($user_notification_info["firebase_token"], $notification_subject, $notification_body, $request_id);
    }
}

function get_request_provider($request_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT users.* FROM users, provider_categories, requests 
        WHERE users.id = provider_categories.provider_id
        AND provider_categories.category_id = requests.category_id
        AND requests.id = :request_id");
    $stmt->bindValue(":request_id", $request_id);
    $stmt->execute();
    $res = $stmt->fetchAll();
    return (count($res) === 1) ? $res[0] : false;
}

function get_request_user($request_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT users.* FROM users, requests 
        WHERE users.id = requests.user_id AND requests.id = :request_id");
    $stmt->bindValue(":request_id", $request_id);
    $stmt->execute();
    $res = $stmt->fetchAll();
    return (count($res) === 1) ? $res[0] : false;
}

/**
 * OPTIMIZED: get_notifications with LIMIT and proper indexing
 */
function get_notifications($limit = 20)
{
    global $pdo;

    if (comercial()) {
        $request_ids = rep_get_request_ids();
        
        if (empty($request_ids)) {
            return ["unread" => 0];
        }
        
        $placeholders = implode(",", array_fill(0, count($request_ids), "?"));
        
        // Fast unread count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE request_id IN ($placeholders) AND status = 0");
        $stmt->execute($request_ids);
        $unread_count = (int)$stmt->fetchColumn();
        
        // Limited notifications for dropdown
        $stmt = $pdo->prepare("SELECT id, request_id, description, status, created_at 
            FROM notifications 
            WHERE request_id IN ($placeholders) 
            ORDER BY status ASC, created_at DESC 
            LIMIT " . (int)$limit);
        $stmt->execute($request_ids);
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $user_id = USER["id"];

        // Fast unread count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE receiver_id = ? AND status = 0");
        $stmt->execute([$user_id]);
        $unread_count = (int)$stmt->fetchColumn();

        // Limited notifications (incluir sender_id para navegación en asesoria)
        $order = IS_MOBILE_APP ? "created_at DESC" : "status ASC, created_at DESC";
        $stmt = $pdo->prepare("SELECT id, request_id, sender_id, description, status, created_at
            FROM notifications
            WHERE receiver_id = ?
            ORDER BY $order
            LIMIT " . (int)$limit);
        $stmt->execute([$user_id]);
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Calculate time_from only for limited results
    foreach ($res as &$notification) {
        $notification['time_from'] = calculate_elapsed_time($notification['created_at']);
    }
    unset($notification);
    
    $res["unread"] = $unread_count;
    return $res;
}

function get_request_customer_notifications($request_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE request_id = :request_id AND receiver_id = :user_id AND status = 0");
    $stmt->bindValue(":request_id", $request_id);
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function calculate_elapsed_time($created_at)
{
    $current_time = new DateTime();
    $notification_time = new DateTime($created_at);
    $interval = $notification_time->diff($current_time);

    if ($interval->days > 3) {
        return 'El ' . $notification_time->format('d/m/Y');
    } elseif ($interval->days >= 1) {
        return 'Hace ' . $interval->days . ' días';
    } elseif ($interval->h >= 1) {
        return 'Hace ' . $interval->h . ' horas';
    } else {
        return 'Hace ' . $interval->i . ' minutos';
    }
}

function user_can_access_notification($notification_id)
{
    if (admin()) return true;

    global $pdo;
    $stmt = $pdo->prepare("SELECT 1 FROM notifications WHERE id = :notification_id AND receiver_id = :user_id");
    $stmt->bindValue(":notification_id", $notification_id);
    $stmt->bindValue(":user_id", USER["id"]);
    $stmt->execute();
    return $stmt->fetch() !== false;
}

function merge_notifications_requests($requests, $notifications = null, $limit = 0)
{
    $notifications = $notifications ?? NOTIFICATIONS;
    $return = [];
    $count = 0;
    
    $request_ids = array_column($requests, "id");
    
    foreach ($notifications as $index => $not) {
        if ($index === "unread") continue;
        if ($limit > 0 && $count >= $limit) break;

        $key = array_search($not["request_id"], $request_ids);
        if ($key === false || !isset($requests[$key])) continue;

        $not["notification_id"] = $not["id"];
        $not["notification_status"] = $not["status"];
        unset($not["id"], $not["status"]);
        
        $return[] = array_merge($requests[$key], $not);
        $count++;
    }
    return $return;
}

function close_pdo()
{
    global $pdo;
    $pdo = null;
    return true;
}

function get_incidents($requests)
{
    global $pdo;
    $request_ids = array_column($requests, "id");

    if (empty($request_ids)) return [];

    $placeholders = implode(',', array_fill(0, count($request_ids), '?'));
    $stmt = $pdo->prepare("SELECT sta.name AS status_name, cat.name AS category_name, inc.* 
        FROM request_incidents inc 
        LEFT JOIN incident_categories cat ON cat.id = inc.incident_category_id
        LEFT JOIN incident_statuses sta ON sta.id = inc.status_id
        WHERE inc.request_id IN ($placeholders)
        ORDER BY status_id ASC, GREATEST(created_at, updated_at) DESC");
    $stmt->execute($request_ids);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_incidents_by_request($request_id)
{
    global $pdo;
    $request_id = (int)$request_id;
    if ($request_id <= 0) return [];

    $stmt = $pdo->prepare("SELECT sta.name AS status_name, cat.name AS category_name, inc.*
        FROM request_incidents inc
        LEFT JOIN incident_categories cat ON cat.id = inc.incident_category_id
        LEFT JOIN incident_statuses sta ON sta.id = inc.status_id
        WHERE inc.request_id = :rid
        ORDER BY inc.status_id ASC, GREATEST(inc.created_at, inc.updated_at) DESC");
    $stmt->bindValue(':rid', $request_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_statuses()
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, status_name FROM requests_statuses ORDER BY id ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_statuses_names()
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT status_name FROM requests_statuses ORDER BY id ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function placeholders($data_array)
{
    return implode(", ", array_fill(0, count($data_array), "?"));
}

function get_customer($customer_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT users.*, rol.name AS role_name, pic.filename AS profile_picture,
        (SELECT COUNT(*) FROM requests WHERE requests.user_id = users.id) AS services_number
        FROM users
        JOIN model_has_roles mhr ON mhr.model_id = users.id
        JOIN roles rol ON rol.id = mhr.role_id
        LEFT JOIN user_pictures pic ON pic.user_id = users.id
        WHERE users.id = :user_id AND mhr.role_id IN (4,5,6)
        ORDER BY users.created_at DESC");
    $stmt->bindValue(":user_id", $customer_id);
    $stmt->execute();
    $customer = $stmt->fetch();
    return $customer ?: [];
}

function get_customer_requests($customer_id)
{
    if (!admin()) return [];

    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM requests WHERE user_id = :customer_id");
    $stmt->bindValue(":customer_id", $customer_id);
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_sales_reps()
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT users.*, 'comercial' AS role_name, codes.code AS sales_rep_code
        FROM users
        JOIN model_has_roles mhr ON mhr.model_id = users.id
        JOIN sales_codes codes ON codes.user_id = users.id
        WHERE mhr.role_id = 7");
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_providers()
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT users.*, 'proveedor' AS role_name, '' AS sales_rep_code
        FROM users
        JOIN model_has_roles mhr ON mhr.model_id = users.id    
        WHERE mhr.role_id = 2
        ORDER BY users.created_at DESC");
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_sales_rep($user_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT users.*, rol.name AS role_name, pic.filename AS profile_picture, codes.code AS code
        FROM users
        JOIN model_has_roles mhr ON mhr.model_id = users.id
        JOIN roles rol ON rol.id = mhr.role_id
        LEFT JOIN user_pictures pic ON pic.user_id = users.id
        JOIN sales_codes codes ON codes.user_id = users.id
        WHERE users.id = :user_id AND mhr.role_id IN (7)
        ORDER BY users.created_at DESC");
    $stmt->bindValue(":user_id", $user_id);
    $stmt->execute();
    $customer = $stmt->fetch();
    return $customer ?: [];
}

function make_pdf($template, $filename, $data = [])
{
    ob_start();
    $aux = [];
    require_once(ROOT_DIR . "/" . PDF_TEMPLATES_DIR . "/$template.php");
    $html = ob_get_clean();

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://coruscant.boldsoftware.es/html-to-pdf',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $html,
        CURLOPT_HTTPHEADER => ['Content-Type: text/html', 'Origin: https://app.facilitame.es'],
    ]);

    $pdf_response = curl_exec($curl);
    
    if (curl_errno($curl)) {
        curl_close($curl);
        return false;
    }
    
    curl_close($curl);
    $filepath = ROOT_DIR . "/" . TEMP_DIR . "/$filename.pdf";
    file_put_contents($filepath, $pdf_response);
    return $filepath;
}

function app_log($target_type, $target_id, $event, $link_type = null, $link_id = null, $triggered_by = null, $data = [])
{
    global $pdo;
    $triggered_by = $triggered_by ?? USER["id"];
    $link_type = $link_type ?? $target_type;
    $link_id = $link_id ?? $target_id;
    $commit = false;

    try {
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $commit = true;
        }

        $stmt = $pdo->prepare("INSERT INTO log SET target_type = :target_type, target_id = :target_id, event = :event, 
            link_type = :link_type, link_id = :link_id, triggered_by = :triggered_by, data = :data");
        $stmt->bindValue(":target_type", $target_type);
        $stmt->bindValue(":target_id", $target_id);
        $stmt->bindValue(":event", $event);
        $stmt->bindValue(":link_type", $link_type);
        $stmt->bindValue(":link_id", $link_id);
        $stmt->bindValue(":triggered_by", $triggered_by);
        $stmt->bindValue(":data", json_encode($data));
        $stmt->execute();

        if ($commit) $pdo->commit();
    } catch (Throwable $e) {
        if ($commit) $pdo->rollBack();
        error_log("Error en app_log: " . $e->getMessage());
    }
}

function get_log()
{
    if (!admin() && !comercial()) {
        header("HTTP/1.1 404");
        exit;
    }

    global $pdo;
    $stmt = $pdo->prepare("SELECT log.*, CONCAT(users.name, ' ', IFNULL(users.lastname, '')) AS triggered_by_name, users.email AS triggered_by_email
        FROM log
        JOIN users ON users.id = log.triggered_by
        ORDER BY log.created_at DESC");
    $stmt->execute();
    return $stmt->fetchAll();
}

function trim_text($text, $max_characters)
{
    return (mb_strlen($text) > $max_characters) ? mb_strimwidth($text, 0, $max_characters, "...") : $text;
}

function get_user($user_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->bindValue(":id", $user_id);
    $stmt->execute();
    $user = $stmt->fetch();
    return $user ?: [];
}

function customer_get_sales_rep($customer_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT sc.user_id FROM customers_sales_codes csc
        JOIN sales_codes sc ON sc.id = csc.sales_code_id
        WHERE csc.customer_id = :customer_id");
    $stmt->bindValue(":customer_id", $customer_id);
    $stmt->execute();
    $res = $stmt->fetch();
    return $res ? $res["user_id"] : false;
}

function customer_get_sales_rep_name($customer_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT u.name, u.lastname
        FROM customers_sales_codes csc
        INNER JOIN sales_codes sc ON csc.sales_code_id = sc.id
        INNER JOIN users u ON sc.user_id = u.id
        WHERE csc.customer_id = :customer_id LIMIT 1");
    $stmt->bindValue(":customer_id", $customer_id, PDO::PARAM_INT);
    $stmt->execute();
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    return $res ? $res["name"] . " " . $res["lastname"] : false;
}

function get_sales_rep_by_customer($customer_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT u.* FROM users u
        JOIN sales_codes sc ON sc.user_id = u.id
        JOIN customers_sales_codes csc ON csc.sales_code_id = sc.id
        WHERE csc.customer_id = :customer_id");
    $stmt->bindValue(":customer_id", $customer_id);
    $stmt->execute();
    $res = $stmt->fetch();
    return $res ?: [];
}

function get_regions()
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT code, name FROM regions ORDER BY name ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

function check_guest()
{
    if (!guest()) return;

    $allowed_endpoints = [
        "/service-get-form", "/app-services", "/app-subcategories-get", "/app-service-form-get",
        "/app-dashboard", "/app-token-save-fcm", "/app-user-get-profile-picture", "/app-user-get-notifications",
        "/app-user-get-request", "/app-user-get-requests", "/app-user-profile", "/app-request-get-chat",
        "/app-requests-get-upcoming-expiration",
    ];

    if (!in_array(PAGE, $allowed_endpoints)) {
        if (IS_MOBILE_APP) {
            json_response("guest", "¿Te interesa Facilítame?\n¡Regístrate ahora y comienza a ahorrar!", 326904065);
        } else {
            json_response("ko", "¿Te interesa Facilítame?<br><br><a href='sign-up'><b>¡Regístrate ahora</b></a> y empieza a hacer tu vida más fácil!", 2561571588, [], "success");
        }
    }
    return true;
}

function get_reviews($requests)
{
    if (empty($requests)) return [];
    return array_filter($requests, fn($req) => $req["status_id"] == 8);
}

function get_sales_rep_codes($sales_rep_id)
{
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT 1 FROM model_has_roles WHERE model_id = :sales_rep_id AND role_id = 7");
    $stmt->bindValue(":sales_rep_id", $sales_rep_id);
    $stmt->execute();
    if (!$stmt->fetch()) return false;

    $stmt = $pdo->prepare("SELECT id, code FROM sales_codes WHERE user_id = :sales_rep_id");
    $stmt->bindValue(":sales_rep_id", $sales_rep_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

function get_excluded_services($sales_rep_id)
{
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT 1 FROM model_has_roles WHERE model_id = :sales_rep_id AND role_id = 7");
    $stmt->bindValue(":sales_rep_id", $sales_rep_id);
    $stmt->execute();
    if (!$stmt->fetch()) return false;

    $stmt = $pdo->prepare("SELECT category_id FROM sales_rep_excludes_category WHERE sales_rep_id = :sales_rep_id");
    $stmt->bindValue(":sales_rep_id", $sales_rep_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function get_customer_sales_rep_code($user_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT sales_codes.code
        FROM sales_codes
        JOIN customers_sales_codes ON customers_sales_codes.sales_code_id = sales_codes.id
        WHERE customers_sales_codes.customer_id = :user_id");
    $stmt->bindValue(":user_id", $user_id);
    $stmt->execute();
    $res = $stmt->fetch();
    return $res ? $res["code"] : "";
}

function getCommissionTypeName($commission_type_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT display FROM commissions WHERE id = :commission_id");
    $stmt->bindValue(":commission_id", $commission_type_id);
    $stmt->execute();
    $res = $stmt->fetch();
    return $res ? $res["display"] : "";
}

function calculateCommission($category_id, $commission)
{
    if (in_array((string)$category_id, ['15', '16', '17', '18'])) {
        return number_format(floatval($commission * 20), 2, ",", ".") . " €";
    }
    return "";
}

function request_requires_total_amount($category_id)
{
    return !in_array(intval($category_id), [1, 16, 17, 18, 15]);
}

function request_get_all()
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT categories.name AS category_display, requests.*, CONCAT(u.name, ' ', u.lastname) AS customer_name
        FROM requests
        JOIN categories ON categories.id = requests.category_id
        JOIN users u ON u.id = requests.user_id
        WHERE requests.deleted_at IS NULL
        ORDER BY created_at DESC");
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($requests as &$request) {
        $request["offers"] = request_get_offers($request["id"]);
        $request["commissions_admin"] = get_offers_commissions($request["id"]);
        $request["sales_rep"] = request_get_sales_rep($request["id"]);
    }
    return $requests;
}

function request_get_offers($request_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM offers WHERE request_id = :request_id");
    $stmt->bindValue(":request_id", $request_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function request_get_sales_rep($request_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT sales_rep.id AS sales_rep_id, sales_rep.name AS sales_rep_name
        FROM requests
        JOIN users AS requestor ON requestor.id = requests.user_id
        JOIN customers_sales_codes csc ON csc.customer_id = requestor.id
        JOIN sales_codes ON sales_codes.id = csc.sales_code_id
        JOIN users AS sales_rep ON sales_rep.id = sales_codes.user_id
        WHERE requests.id = :request_id");
    $stmt->bindValue(":request_id", $request_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function commission_get_detail($request)
{
    $sales_rep = !empty($request["sales_rep"]);
    $sales_rep_commission = 0.0;
    $admin_commission = 0.0;

    if ($request["active_offer"]["expires_at"] > date("Y-m-d")) {
        $category_id = intval($request["category_id"]);
        $total_amount = floatval($request["active_offer"]["total_amount"] ?? 0);
        $commision = floatval($request["active_offer"]["commision"] ?? 0);
        $is_current_month = activated_current_month($request["active_offer"]);

        switch ($category_id) {
            case 15: case 16: case 17: case 18: // Energía, Telefonía, Alarmas, Renting
                if ($is_current_month) {
                    $admin_commission = 150.00 * $commision;
                    if ($sales_rep) {
                        $sales_rep_commission = 20.00 * $commision;
                        $admin_commission -= $sales_rep_commission;
                    }
                }
                break;

            case 47: case 48: // Imagen, Vídeo
                if ($is_current_month) {
                    $admin_commission = 0.10 * $total_amount;
                    if ($sales_rep) {
                        $sales_rep_commission = 0.20 * $admin_commission;
                        $admin_commission *= 0.80;
                    }
                }
                break;

            case 49: // Reformas
                if ($is_current_month) {
                    $rate = ($total_amount >= 2000) ? 0.04 : 0.08;
                    $admin_commission = $rate * $total_amount;
                    if ($sales_rep) {
                        $sales_rep_commission = 0.20 * $admin_commission;
                        $admin_commission *= 0.80;
                    }
                }
                break;

            case 26: case 23: case 29: case 22: case 28: case 27: case 21: case 25: // Marketing Digital
                if ($is_current_month) {
                    $admin_commission = 0.50 * $total_amount;
                    if ($sales_rep) {
                        $sales_rep_commission = 0.20 * $admin_commission;
                        $admin_commission *= 0.80;
                    }
                }
                break;

            case 30: // Análisis sociológico big data
                if ($is_current_month) {
                    $admin_commission = 0.20 * $total_amount;
                    if ($sales_rep) {
                        $sales_rep_commission = 0.20 * $admin_commission;
                        $admin_commission *= 0.80;
                    }
                }
                break;

            case 24: // Kit digital
                if ($is_current_month) {
                    if ($total_amount <= 3000) {
                        $admin_commission = 0.20 * $total_amount;
                        $rep_rate = 0.15;
                    } elseif ($total_amount >= 6000 && $total_amount < 12000) {
                        $admin_commission = 0.20 * $total_amount;
                        $rep_rate = 0.20;
                    } elseif ($total_amount >= 12000) {
                        $admin_commission = 0.30 * $total_amount;
                        $rep_rate = 0.30;
                    } else {
                        $rep_rate = 0;
                    }
                    if ($sales_rep && isset($rep_rate)) {
                        $sales_rep_commission = $rep_rate * $admin_commission;
                        $admin_commission *= (1 - $rep_rate);
                    }
                }
                break;

            case 37: case 34: case 38: case 33: case 35: case 32: case 39: // Servicios jurídicos
                if ($is_current_month) {
                    $admin_commission = 0.15 * $total_amount;
                    if ($sales_rep) {
                        $sales_rep_commission = 0.20 * $admin_commission;
                        $admin_commission *= 0.80;
                    }
                }
                break;

            case 36: // Subvenciones
                if ($is_current_month) {
                    if ($sales_rep) {
                        $rep_rate = ($total_amount < 12000) ? 0.20 : 0.30;
                        $sales_rep_commission = $rep_rate * $admin_commission;
                        $admin_commission *= (1 - $rep_rate);
                    } else {
                        $admin_commission = 0.02 * $total_amount;
                    }
                }
                break;

            case 41: case 42: // Mantenimiento informático, Centralitas
                if ($is_current_month) {
                    $admin_commission = 0.20 * $total_amount;
                    if ($sales_rep) {
                        $sales_rep_commission = 0.20 * $admin_commission;
                        $admin_commission *= 0.80;
                    }
                }
                break;

            case 43: // Mantenimiento TPV
                if ($is_current_month) {
                    $admin_commission = 0.15 * $total_amount;
                    if ($sales_rep) {
                        $sales_rep_commission = 0.15 * $admin_commission;
                        $admin_commission *= 0.85;
                    }
                }
                break;

            case 45: // Kit Digital para TPV
                if ($is_current_month) {
                    $admin_commission = 0.20 * $total_amount;
                    if ($sales_rep) {
                        $sales_rep_commission = 0.20 * $admin_commission;
                        $admin_commission *= 0.80;
                    }
                }
                break;
        }
    }

    // Process admin commissions
    if (!empty($request["commissions_admin"])) {
        $selected_year_month = date("Ym", strtotime($_SESSION["commissions_selected_year"] . "-" . $_SESSION["commissions_selected_month"]));
        
        foreach ($request["commissions_admin"] as $commission_admin) {
            $deactivated_at = $commission_admin["deactivated_at"] ? date("Ym", strtotime($commission_admin["deactivated_at"])) : null;
            $activated_at = date("Ym", strtotime($commission_admin["activated_at"]));

            if (($deactivated_at === null || $deactivated_at > $selected_year_month) && $activated_at <= $selected_year_month) {
                if ($commission_admin["recurring"] == "0" && activated_current_month($commission_admin)) {
                    $admin_commission += $commission_admin["value"];
                } elseif ($commission_admin["recurring"] == "1") {
                    $admin_commission += $commission_admin["value"];
                }
            }
        }
    }

    return ["sales_rep_commission" => $sales_rep_commission, "admin_commission" => $admin_commission];
}

function activated_current_month($offer)
{
    $current_year_month = date("Y-m", strtotime($_SESSION["commissions_selected_year"] . "-" . $_SESSION["commissions_selected_month"]));
    return $current_year_month === date("Y-m", strtotime($offer["activated_at"]));
}

function get_offers_commissions($request_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM commissions_admin WHERE request_id = :request_id");
    $stmt->bindValue(":request_id", $request_id);
    $stmt->execute();
    return $stmt->fetchAll();
}

function app_get_category_image_uri($category_id)
{
    $category_map = [
        '1' => 'seguros', '2' => 'seguros', '3' => 'seguros', '4' => 'seguros', '9' => 'seguros',
        '15' => 'energia-gas', '16' => 'telefonia', '17' => 'alarmas', '18' => 'renting',
        '20' => 'marketing-digital', '21' => 'marketing-digital', '22' => 'marketing-digital',
        '23' => 'marketing-digital', '24' => 'marketing-digital', '25' => 'marketing-digital',
        '26' => 'marketing-digital', '27' => 'marketing-digital', '28' => 'marketing-digital',
        '29' => 'marketing-digital', '30' => 'marketing-digital',
        '31' => 'servicios-juridicos', '32' => 'servicios-juridicos', '33' => 'servicios-juridicos',
        '34' => 'servicios-juridicos', '35' => 'servicios-juridicos', '36' => 'servicios-juridicos',
        '37' => 'servicios-juridicos', '38' => 'servicios-juridicos', '39' => 'servicios-juridicos',
        '40' => 'servicios-informaticos', '41' => 'servicios-informaticos', '42' => 'servicios-informaticos',
        '43' => 'servicios-informaticos', '44' => 'servicios-informaticos', '45' => 'servicios-informaticos',
        '46' => 'imagen-video', '47' => 'imagen-video', '48' => 'imagen-video',
        '49' => 'reformas', '50' => 'tpv-verifactu', '56' => 'marketing-olfativo', '57' => 'control-plagas',
    ];

    $category_name = $category_map[(string)$category_id] ?? null;
    return $category_name ? ROOT_URL . "/" . MEDIA_DIR . "/app-category-" . $category_name . ".png" : "";
}

// ========================================
// FUNCIONES DE CHAT ASESORÍA
// ========================================

function get_advisory_messages($advisory_id, $customer_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM advisory_messages WHERE advisory_id = ? AND customer_id = ? ORDER BY created_at ASC");
    $stmt->execute([$advisory_id, $customer_id]);
    return $stmt->fetchAll();
}

function get_advisory_conversations($advisory_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT 
            u.id as customer_id, u.name, u.lastname, u.email,
            (SELECT content FROM advisory_messages WHERE advisory_id = ca.advisory_id AND customer_id = u.id ORDER BY created_at DESC LIMIT 1) as last_message,
            (SELECT created_at FROM advisory_messages WHERE advisory_id = ca.advisory_id AND customer_id = u.id ORDER BY created_at DESC LIMIT 1) as last_message_at,
            (SELECT COUNT(*) FROM advisory_messages WHERE advisory_id = ca.advisory_id AND customer_id = u.id AND is_read = 0 AND sender_type = 'customer') as unread_count
        FROM users u
        INNER JOIN customers_advisories ca ON ca.customer_id = u.id
        WHERE ca.advisory_id = :advisory_id
        ORDER BY last_message_at DESC");
    $stmt->bindValue(":advisory_id", $advisory_id);
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_customer_advisory_id($customer_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT advisory_id FROM customers_advisories WHERE customer_id = :customer_id LIMIT 1");
    $stmt->bindValue(":customer_id", $customer_id);
    $stmt->execute();
    $result = $stmt->fetch();
    return $result ? $result['advisory_id'] : false;
}

function get_appointment_chat_messages($appointment_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT am.*, 
        CASE WHEN am.sender_type = 'customer' THEN CONCAT(u.name, ' ', u.lastname) ELSE 'Asesoría' END as sender_name
        FROM advisory_messages am
        LEFT JOIN advisory_appointments aa ON aa.id = am.appointment_id
        LEFT JOIN users u ON u.id = aa.customer_id
        WHERE am.appointment_id = ?
        ORDER BY am.created_at ASC");
    $stmt->execute([$appointment_id]);
    return $stmt->fetchAll();
}

function send_appointment_message_from_advisory($appointment_id, $advisory_id, $customer_id, $message)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO advisory_messages (advisory_id, customer_id, appointment_id, sender_type, content, is_read, created_at) VALUES (?, ?, ?, 'advisory', ?, 0, NOW())");
        $stmt->execute([$advisory_id, $customer_id, $appointment_id, $message]);
        return ['status' => 'ok', 'message_id' => $pdo->lastInsertId()];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

function send_appointment_message_from_customer($appointment_id, $advisory_id, $customer_id, $message)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO advisory_messages (advisory_id, customer_id, appointment_id, sender_type, content, is_read, created_at) VALUES (?, ?, ?, 'customer', ?, 0, NOW())");
        $stmt->execute([$advisory_id, $customer_id, $appointment_id, $message]);
        return ['status' => 'ok', 'message_id' => $pdo->lastInsertId()];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}

function mark_appointment_messages_read($appointment_id, $reader_type)
{
    global $pdo;
    $sender_type = ($reader_type === 'advisory') ? 'customer' : 'advisory';
    $stmt = $pdo->prepare("UPDATE advisory_messages SET is_read = 1 WHERE appointment_id = ? AND sender_type = ? AND is_read = 0");
    $stmt->execute([$appointment_id, $sender_type]);
    return $stmt->rowCount();
}

function count_unread_appointment_messages($appointment_id, $for_type)
{
    global $pdo;
    $sender_type = ($for_type === 'advisory') ? 'customer' : 'advisory';
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM advisory_messages WHERE appointment_id = ? AND sender_type = ? AND is_read = 0");
    $stmt->execute([$appointment_id, $sender_type]);
    return (int)$stmt->fetchColumn();
}

function get_customer_requests_for_advisory($customer_id, $advisory_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT 1 FROM customers_advisories WHERE customer_id = ? AND advisory_id = ?");
    $stmt->execute([$customer_id, $advisory_id]);
    if (!$stmt->fetch()) return [];

    $stmt = $pdo->prepare("SELECT req.*, cat.name AS category_name, sta.status_name AS status
        FROM requests req
        LEFT JOIN categories cat ON cat.id = req.category_id
        LEFT JOIN requests_statuses sta ON sta.id = req.status_id
        WHERE req.user_id = ? AND req.deleted_at IS NULL
        ORDER BY req.created_at DESC");
    $stmt->execute([$customer_id]);
    return $stmt->fetchAll();
}

// ============================================
// HISTORIAL DE CITAS
// ============================================

function log_appointment_change($appointment_id, $user_id, $user_type, $action, $field_changed = null, $old_value = null, $new_value = null, $notes = null)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO advisory_appointment_history (appointment_id, user_id, user_type, action, field_changed, old_value, new_value, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$appointment_id, $user_id, $user_type, $action, $field_changed, $old_value, $new_value, $notes]);
        return true;
    } catch (Exception $e) {
        error_log("Error en log_appointment_change: " . $e->getMessage());
        return false;
    }
}

function get_appointment_history($appointment_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT h.*, u.name as user_name, u.lastname as user_lastname
        FROM advisory_appointment_history h
        LEFT JOIN users u ON u.id = h.user_id
        WHERE h.appointment_id = ?
        ORDER BY h.created_at DESC");
    $stmt->execute([$appointment_id]);
    return $stmt->fetchAll();
}

function translate_history_action($action, $field = null, $old_value = null, $new_value = null)
{
    $actions = [
        'created' => 'Cita creada', 'status_changed' => 'Estado cambiado', 'edited' => 'Cita editada',
        'cancelled' => 'Cita cancelada', 'scheduled' => 'Cita agendada', 'rescheduled' => 'Cita reprogramada',
        'finalized' => 'Cita finalizada', 'message_sent' => 'Mensaje enviado', 'notes_updated' => 'Notas actualizadas'
    ];
    $fields = [
        'scheduled_date' => 'Fecha programada', 'type' => 'Tipo de cita', 'department' => 'Departamento',
        'status' => 'Estado', 'reason' => 'Motivo', 'notes_advisory' => 'Notas internas', 'preferred_time' => 'Horario preferido'
    ];
    $statuses = ['solicitado' => 'Pendiente', 'agendado' => 'Agendada', 'finalizado' => 'Finalizada', 'cancelado' => 'Cancelada'];

    $text = $actions[$action] ?? $action;
    
    if ($field && isset($fields[$field])) {
        $text .= ": " . $fields[$field];
        if ($field === 'status') {
            $text .= " de '" . ($statuses[$old_value] ?? $old_value) . "' a '" . ($statuses[$new_value] ?? $new_value) . "'";
        } elseif ($field === 'scheduled_date' && $new_value) {
            $text .= " a " . date('d/m/Y H:i', strtotime($new_value));
        }
    }
    return $text;
}

function generate_google_calendar_url($appointment)
{
    $start = $appointment['scheduled_date'] ?? null;
    if (!$start) return null;

    $start_dt = new DateTime($start);
    $end_dt = clone $start_dt;
    $end_dt->add(new DateInterval('PT1H'));

    $title = "Cita - " . ($appointment['type_label'] ?? ucfirst($appointment['type'] ?? 'Cita'));
    $description = "Departamento: " . ($appointment['department_label'] ?? $appointment['department'] ?? '-');
    if (!empty($appointment['reason'])) $description .= "\n\nMotivo: " . $appointment['reason'];
    if (!empty($appointment['customer_name'])) $description .= "\n\nCliente: " . $appointment['customer_name'];
    if (!empty($appointment['advisory_name'])) $description .= "\n\nAsesoría: " . $appointment['advisory_name'];

    return 'https://calendar.google.com/calendar/render?' . http_build_query([
        'action' => 'TEMPLATE',
        'text' => $title,
        'dates' => $start_dt->format('Ymd\THis') . '/' . $end_dt->format('Ymd\THis'),
        'details' => $description,
        'sf' => 'true',
        'output' => 'xml'
    ]);
}

function generate_ics_content($appointment)
{
    $start = $appointment['scheduled_date'] ?? null;
    if (!$start) return null;

    $start_dt = new DateTime($start);
    $end_dt = clone $start_dt;
    $end_dt->add(new DateInterval('PT1H'));

    $title = "Cita - " . ($appointment['type_label'] ?? ucfirst($appointment['type'] ?? 'Cita'));
    $description = "Departamento: " . ($appointment['department_label'] ?? $appointment['department'] ?? '-');
    if (!empty($appointment['reason'])) {
        $description .= "\\nMotivo: " . str_replace(["\r\n", "\n", "\r"], "\\n", $appointment['reason']);
    }

    return "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Facilitame//Citas//ES\r\nCALSCALE:GREGORIAN\r\nMETHOD:PUBLISH\r\n" .
        "BEGIN:VEVENT\r\nUID:" . uniqid('apt-') . "@facilitame.es\r\n" .
        "DTSTART:" . $start_dt->format('Ymd\THis') . "\r\nDTEND:" . $end_dt->format('Ymd\THis') . "\r\n" .
        "SUMMARY:{$title}\r\nDESCRIPTION:{$description}\r\nSTATUS:CONFIRMED\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
}

function send_appointment_email($appointment_id, $type, $recipient = 'both')
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT aa.*, u.name as customer_name, u.lastname as customer_lastname, u.email as customer_email,
        a.razon_social as advisory_name, ua.email as advisory_email, ua.name as advisory_user_name
        FROM advisory_appointments aa
        INNER JOIN users u ON u.id = aa.customer_id
        INNER JOIN advisories a ON a.id = aa.advisory_id
        INNER JOIN users ua ON ua.id = a.user_id
        WHERE aa.id = ?");
    $stmt->execute([$appointment_id]);
    $apt = $stmt->fetch();
    if (!$apt) return false;

    $customer_name = trim($apt['customer_name'] . ' ' . $apt['customer_lastname']);
    $types = ['llamada' => 'Llamada telefónica', 'reunion_presencial' => 'Reunión presencial', 'reunion_virtual' => 'Videollamada'];
    $depts = ['contabilidad' => 'Contabilidad', 'fiscalidad' => 'Fiscalidad', 'laboral' => 'Laboral', 'gestion' => 'Gestión'];
    $subjects = ['created' => 'Nueva solicitud de cita', 'scheduled' => 'Tu cita ha sido agendada', 'rescheduled' => 'Tu cita ha sido reprogramada', 'cancelled' => 'Cita cancelada', 'finalized' => 'Cita finalizada', 'reminder' => 'Recordatorio de cita'];

    $apt['type_label'] = $types[$apt['type']] ?? $apt['type'];
    $apt['department_label'] = $depts[$apt['department']] ?? $apt['department'];
    $apt['customer_name'] = $customer_name;

    $data = [
        'customer_name' => $customer_name,
        'advisory_name' => $apt['advisory_name'],
        'type_label' => $apt['type_label'],
        'dept_label' => $apt['department_label'],
        'scheduled_date' => $apt['scheduled_date'] ? date('d/m/Y \a \l\a\s H:i', strtotime($apt['scheduled_date'])) : 'Por confirmar',
        'reason' => $apt['reason'],
        'google_url' => generate_google_calendar_url($apt),
        'cancellation_reason' => $apt['cancellation_reason'] ?? null
    ];

    $subject = $subjects[$type] ?? 'Actualización de cita';

    if ($recipient === 'customer' || $recipient === 'both') {
        send_mail($apt['customer_email'], $customer_name, $subject . ' - ' . $apt['advisory_name'], 
            generate_appointment_email_body($type, 'customer', $data), 'apt-' . $appointment_id . '-' . time());
    }

    if ($recipient === 'advisory' || $recipient === 'both') {
        send_mail($apt['advisory_email'], $apt['advisory_user_name'], $subject . ' - ' . $customer_name,
            generate_appointment_email_body($type, 'advisory', $data), 'apt-' . $appointment_id . '-' . time());
    }

    return true;
}

function generate_appointment_email_body($type, $recipient, $data)
{
    $greeting = $recipient === 'customer' ? "Hola " . htmlspecialchars($data['customer_name']) : "Hola";
    $header_titles = ['created' => '📅 Nueva Solicitud de Cita', 'scheduled' => '✅ Cita Agendada', 'rescheduled' => '🔄 Cita Reprogramada', 'cancelled' => '❌ Cita Cancelada', 'finalized' => '✔️ Cita Finalizada', 'reminder' => '⏰ Recordatorio de Cita'];
    
    $messages = [
        'created' => ['customer' => "Tu solicitud de cita ha sido recibida. La asesoría <strong>{$data['advisory_name']}</strong> revisará tu solicitud.", 'advisory' => "Nueva solicitud de cita de <strong>{$data['customer_name']}</strong>."],
        'scheduled' => ['customer' => "Tu cita con <strong>{$data['advisory_name']}</strong> confirmada para <strong>{$data['scheduled_date']}</strong>.", 'advisory' => "Cita agendada con <strong>{$data['customer_name']}</strong> para <strong>{$data['scheduled_date']}</strong>."],
        'rescheduled' => ['customer' => "Tu cita reprogramada para <strong>{$data['scheduled_date']}</strong>.", 'advisory' => "Cita reprogramada con <strong>{$data['customer_name']}</strong> para <strong>{$data['scheduled_date']}</strong>."],
        'cancelled' => ['customer' => "Tu cita con <strong>{$data['advisory_name']}</strong> ha sido cancelada.", 'advisory' => "Cita con <strong>{$data['customer_name']}</strong> cancelada."],
        'finalized' => ['customer' => "Tu cita ha sido marcada como finalizada.", 'advisory' => "Cita con <strong>{$data['customer_name']}</strong> finalizada."]
    ];

    $message = $messages[$type][$recipient] ?? '';
    $header_title = $header_titles[$type] ?? '📅 Actualización de Cita';

    $html = "<div style='font-family:Segoe UI,Arial;max-width:600px;margin:0 auto'>";
    $html .= "<div style='background:linear-gradient(135deg,#00c2cb,#0ea5e9);padding:30px;text-align:center;border-radius:12px 12px 0 0'><h1 style='color:white;margin:0'>{$header_title}</h1></div>";
    $html .= "<div style='background:#f8fafc;padding:30px'><div style='background:white;border-radius:12px;padding:25px;margin-bottom:20px'>";
    $html .= "<p><strong>{$greeting},</strong></p><p>{$message}</p>";
    
    if ($type === 'cancelled' && !empty($data['cancellation_reason'])) {
        $html .= "<div style='background:#fee2e2;border-left:4px solid #ef4444;padding:15px;border-radius:8px'><strong>Motivo:</strong> " . htmlspecialchars($data['cancellation_reason']) . "</div>";
    }
    
    $html .= "</div>";
    
    if ($type !== 'cancelled') {
        $html .= "<div style='background:white;border-radius:12px;padding:25px'><h3>Detalles</h3>";
        $html .= "<p><strong>Tipo:</strong> " . htmlspecialchars($data['type_label']) . "</p>";
        $html .= "<p><strong>Departamento:</strong> " . htmlspecialchars($data['dept_label']) . "</p>";
        $html .= "<p><strong>Fecha:</strong> " . htmlspecialchars($data['scheduled_date']) . "</p>";
        if (!empty($data['reason'])) $html .= "<p><strong>Motivo:</strong> " . htmlspecialchars($data['reason']) . "</p>";
        if (!empty($data['google_url']) && in_array($type, ['scheduled', 'rescheduled'])) {
            $html .= "<p style='text-align:center'><a href='" . htmlspecialchars($data['google_url']) . "' style='display:inline-block;background:#00c2cb;color:white;padding:12px 24px;border-radius:8px;text-decoration:none'>📅 Añadir a Google Calendar</a></p>";
        }
        $html .= "</div>";
    }
    
    $html .= "<p style='text-align:center'><a href='" . ROOT_URL . "'>Accede a Facilítame</a></p></div>";
    $html .= "<div style='background:#1e293b;padding:20px;text-align:center;border-radius:0 0 12px 12px'><p style='color:#94a3b8;margin:0'>© " . date('Y') . " Facilítame</p></div></div>";
    
    return $html;
}

function can_edit_appointment($appointment) { return in_array($appointment['status'], ['solicitado', 'agendado']); }
function can_cancel_appointment($appointment) { return in_array($appointment['status'], ['solicitado', 'agendado']); }

function get_allowed_status_transitions($current_status)
{
    return [
        'solicitado' => ['agendado', 'cancelado'],
        'agendado' => ['finalizado', 'cancelado', 'solicitado'],
        'finalizado' => [],
        'cancelado' => ['solicitado']
    ][$current_status] ?? [];
}

function validate_appointment_data($data, $is_update = false)
{
    $errors = [];
    $valid_types = ['llamada', 'reunion_presencial', 'reunion_virtual'];
    $valid_departments = ['contabilidad', 'fiscalidad', 'laboral', 'gestion'];
    $valid_times = ['manana', 'tarde', 'especifico'];

    if ((!$is_update || isset($data['type'])) && (empty($data['type']) || !in_array($data['type'], $valid_types))) {
        $errors[] = 'Tipo de cita no válido';
    }
    if ((!$is_update || isset($data['department'])) && (empty($data['department']) || !in_array($data['department'], $valid_departments))) {
        $errors[] = 'Departamento no válido';
    }
    if (!empty($data['preferred_time']) && !in_array($data['preferred_time'], $valid_times)) {
        $errors[] = 'Horario preferido no válido';
    }
    if (!empty($data['scheduled_date']) && strtotime($data['scheduled_date']) === false) {
        $errors[] = 'Fecha programada no válida';
    }
    return $errors;
}

function get_appointment_full($appointment_id, $advisory_id = null, $customer_id = null)
{
    global $pdo;
    $sql = "SELECT aa.*, u.name as customer_name, u.lastname as customer_lastname, u.email as customer_email, 
        u.phone as customer_phone, u.nif_cif as customer_nif, a.razon_social as advisory_name, ua.email as advisory_email, ua.phone as advisory_phone
        FROM advisory_appointments aa
        INNER JOIN users u ON u.id = aa.customer_id
        INNER JOIN advisories a ON a.id = aa.advisory_id
        INNER JOIN users ua ON ua.id = a.user_id
        WHERE aa.id = ?";
    $params = [$appointment_id];
    
    if ($advisory_id !== null) { $sql .= " AND aa.advisory_id = ?"; $params[] = $advisory_id; }
    if ($customer_id !== null) { $sql .= " AND aa.customer_id = ?"; $params[] = $customer_id; }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

function count_appointments_by_status($advisory_id)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM advisory_appointments WHERE advisory_id = ? GROUP BY status");
    $stmt->execute([$advisory_id]);
    
    $counts = ['solicitado' => 0, 'agendado' => 0, 'finalizado' => 0, 'cancelado' => 0, 'total' => 0];
    while ($row = $stmt->fetch()) {
        $counts[$row['status']] = (int)$row['count'];
        $counts['total'] += (int)$row['count'];
    }
    return $counts;
}