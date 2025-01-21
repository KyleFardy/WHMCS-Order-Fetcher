<?php

class WHMCSOrderFetcher
{
    private $database;
    private $apiUrl;
    private $apiUsername;
    private $apiPassword;

    public function __construct()
    {
        $this->database = new PDO(
            "mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_NAME'),
            getenv('DB_USER'),
            getenv('DB_PASSWORD')
        );
        $this->database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->apiUrl = getenv('API_URL');
        $this->apiUsername = getenv('API_USERNAME');
        $this->apiPassword = getenv('API_PASSWORD');
    }
    private function isValidOrderNumber($orderNumber)
    {
        if (!is_numeric($orderNumber) || strlen($orderNumber) !== 10) {
            return false;
        }
        if ((int)$orderNumber <= 0) {
            return false;
        }
        return true;
    }
    public function fetchOrder($orderNumber)
    {
        if (!$this->isValidOrderNumber($orderNumber)) {
            return $this->jsonResponse("error", 'Invalid/Empty Order Number');
        }
        try {
            $order = $this->database->prepare('SELECT id FROM tblorders WHERE ordernum = :ordernum');
            $order->bindParam(':ordernum', self::sanitizeInput($orderNumber), PDO::PARAM_STR);
            $order->execute();
            $order = $order->fetch(PDO::FETCH_OBJ);
            if ($order) {
                return $this->fetchOrderDetailsFromAPI($order->id);
            } else {
                return $this->jsonResponse("error", 'Order Not Found');
            }
        } catch (PDOException $e) {
            error_log('Database Error: ' . $e->getMessage());
            return $this->jsonResponse("error", 'An Error Occurred', $e->getMessage());
        } catch (Exception $e) {
            error_log('General Error: ' . $e->getMessage());
            return $this->jsonResponse("error", 'An Unexpected Error Occurred', $e->getMessage());
        }
    }
    static function sanitizeInput($input)
    {
        if (is_array($input)) {
            return array_map('self::sanitizeInput', $input);
        }
        return nl2br(strip_tags(htmlspecialchars(trim(str_replace("\0", '', $input)), ENT_QUOTES, 'UTF-8'), '<p><a><b><i><u><strong><em><code><img>'));
    }
    private function fetchOrderDetailsFromAPI($orderId)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            http_build_query(
                array(
                    'action' => 'GetOrders',
                    'username' => self::sanitizeInput($this->apiUsername),
                    'password' => self::sanitizeInput($this->apiPassword),
                    'id' => self::sanitizeInput($orderId),
                    'responsetype' => 'json',
                )
            )
        );
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            curl_close($ch);
            return $this->jsonResponse("error", "API Error", curl_error($ch));
        }
        curl_close($ch);
        $responseData = json_decode($response);
        if (isset($responseData->result) && $responseData->result === 'success') {
            return $this->jsonResponse("success", $responseData->orders->order[0]);
        } else {
            return $this->jsonResponse("error", 'Error Fetching Order Information From WHMCS');
        }
    }
    private function jsonResponse($status, $data, $extra = null)
    {
        header('Content-Type: application/json');
        $response = [
            'status' => $status,
            'order' => $data
        ];
        if ($extra !== null) {
            if (is_array($extra)) {
                $response = array_merge($response, $extra);
            } else {
                $response['extra'] = $extra;
            }
        }
        echo json_encode($response);
        exit;
    }
}
