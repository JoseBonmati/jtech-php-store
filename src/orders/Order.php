<?php

    //Order Entity Class
    class Order {

        private ?int $id = null;
        private ?int $user_id = null;
        private ?string $date = null;
        private ?float $total = null;
        private ?string $status = null;
        private ?string $payment_method = null;
        private ?string $shipping_address = null;
        private ?string $shipping_city = null;
        private ?string $shipping_province = null;
        private ?string $shipping_phone = null;
        private ?string $user_name = null;

        public function getId(): ?int {
            return $this->id;
        }

        public function getUserId(): ?int {
            return $this->user_id;
        }

        public function getDate(): ?string {
            return $this->date;
        }

        public function getTotal(): ?float {
            return $this->total;
        }

        public function getStatus(): ?string {
            return $this->status;
        }

        public function getPaymentMethod(): ?string {
            return $this->payment_method;
        }

        public function getShippingAddress(): ?string {
            return $this->shipping_address;
        }

        public function getShippingCity(): ?string {
            return $this->shipping_city;
        }

        public function getShippingProvince(): ?string {
            return $this->shipping_province;
        }

        public function getShippingPhone(): ?string {
            return $this->shipping_phone;
        }

        public function getUserName(): ?string {
            return $this->user_name;
        }
    }

    //Fetches orders with pagination, sorting, and mapping to Order class
    function getOrders(PDO $db, int $page, int $perPage, string $sortBy, string $sortDir, ?int $userIdOnly = null): array {

        $offset = ($page - 1) * $perPage;

        // White-list mapping parameter keys to avoid SQL Injection via ORDER BY
        $allowedColumns = [
            "id" => "p.id",
            "date" => "p.fecha",
            "total" => "p.total",
            "status" => "p.estado",
            "user_name" => "u.nombre"
        ];
        
        // Fallback to safe defaults if parameters are invalid
        $orderField = $allowedColumns[$sortBy] ?? "p.id";
        $sortDir = strtoupper($sortDir) === "DESC" ? "DESC" : "ASC";

        $userFilter = "";
        if ($userIdOnly !== null) {
            $userFilter = "WHERE p.id_usuario = :userId";
        }

        // Clean SQL query with aliases to hydrate the Order class seamlessly
        $sql = "SELECT p.id, p.id_usuario AS user_id, p.fecha AS date, p.total, p.estado AS status, p.tipo_pago AS payment_method, p.direccion_envio AS shipping_address, 
                p.localidad_envio AS shipping_city, p.provincia_envio AS shipping_province, p.telefono_envio AS shipping_phone, u.nombre AS user_name FROM pedidos p 
                JOIN usuarios u ON p.id_usuario = u.id $userFilter ORDER BY $orderField $sortDir LIMIT :offset, :limit";

        $stmt = $db->prepare($sql);

        if ($userIdOnly !== null) {
            $stmt->bindValue(":userId", $userIdOnly, PDO::PARAM_INT);
        }

        $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
        $stmt->bindValue(":limit", $perPage, PDO::PARAM_INT);
        $stmt->execute();

        $stmt->setFetchMode(PDO::FETCH_CLASS, "Order");
        return $stmt->fetchAll();
    }

?>