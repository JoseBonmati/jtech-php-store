<?php

    //Report Entity Class
    class Report {

        private ?string $month = null;
        private ?float $revenue = null;
        private ?string $product_name = null;
        private ?int $quantity_sold = null;
        private ?float $total_sales = null;

        public function getMonth(): ?string { 
            return $this->month; 
        }

        public function getRevenue(): ?float { 
            return $this->revenue; 
        }

        public function getProductName(): ?string { 
            return $this->product_name; 
        }

        public function getQuantitySold(): ?int { 
            return $this->quantity_sold; 
        }

        public function getTotalSales(): ?float { 
            return $this->total_sales; 
        }
    }

    //Gets the total lifetime sales amount across all orders.
    function getTotalSales(PDO $db): float {
        $sql = $db->query("SELECT SUM(total) AS total_sales FROM pedidos");
        return (float) ($sql->fetchColumn() ?: 0);
    }

    //Gets the top 10 best-selling products based on quantity sold.
    function getTopSellingProducts(PDO $db): array {
        $sql = $db->prepare("SELECT p.nombre AS product_name, SUM(dp.cantidad) AS quantity_sold FROM detalles_pedidos dp INNER JOIN productos p ON p.id = dp.id_producto
                             GROUP BY dp.id_producto ORDER BY quantity_sold DESC LIMIT 10");

        $sql->execute();
        $sql->setFetchMode(PDO::FETCH_CLASS, "Report");
        return $sql->fetchAll();
    }

    //Gets total revenue grouped by month (YYYY-MM).
    function getMonthlyRevenue(PDO $db): array {
        $sql = $db->prepare("SELECT DATE_FORMAT(fecha, '%Y-%m') AS month, SUM(total) AS revenue FROM pedidos GROUP BY month ORDER BY month ASC");

        $sql->execute();
        $sql->setFetchMode(PDO::FETCH_CLASS, "Report");
        return $sql->fetchAll();
    }

?>