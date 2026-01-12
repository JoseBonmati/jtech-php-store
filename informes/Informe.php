<?php

    // Clase Informe
    class Informe {

        private $mes;
        private $ingresos;
        private $producto;
        private $cantidadVendida;
        private $totalVentas;

        public function getMes() { 
            return $this->mes; 
        }

        public function getIngresos() { 
            return $this->ingresos; 
        }

        public function getProducto() { 
            return $this->producto; 
        }

        public function getCantidadVendida() { 
            return $this->cantidadVendida; 
        }

        public function getTotalVentas() { 
            return $this->totalVentas; 
        }
    }

    function obtenerVentasTotales($con) {
        $sql = $con->query("SELECT SUM(total) AS totalVentas FROM pedidos");
        return $sql->fetchColumn() ?: 0;
    }

    function obtenerProductosMasVendidos($con) {

        $sql = $con->prepare("SELECT p.nombre AS producto, SUM(dp.cantidad) AS cantidadVendida FROM detalles_pedidos dp INNER JOIN productos p ON p.id = dp.id_producto
                              GROUP BY dp.id_producto ORDER BY cantidadVendida DESC LIMIT 10");

        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }

    function obtenerIngresosMensuales($con) {

        $sql = $con->prepare("SELECT DATE_FORMAT(fecha, '%Y-%m') AS mes, SUM(total) AS ingresos FROM pedidos GROUP BY mes ORDER BY mes ASC");

        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_ASSOC);
    }

?>
