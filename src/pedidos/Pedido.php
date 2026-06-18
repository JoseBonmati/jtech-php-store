<?php

    // Clase entidad Pedido
    class Pedido {

        private $id;
        private $id_usuario;
        private $fecha;
        private $total;
        private $estado;
        private $tipo_pago;
        private $direccion_envio;
        private $localidad_envio;
        private $provincia_envio;
        private $telefono_envio;
        private $usuarioNombre;

        public function getId() {
            return $this->id;
        }

        public function getIdUsuario() {
            return $this->id_usuario;
        }

        public function getFecha() {
            return $this->fecha;
        }

        public function getTotal() {
            return $this->total;
        }

        public function getEstado() {
            return $this->estado;
        }

        public function getTipoPago() {
            return $this->tipo_pago;
        }

        public function getDireccionEnvio() {
            return $this->direccion_envio;
        }

        public function getLocalidadEnvio() {
            return $this->localidad_envio;
        }

        public function getProvinciaEnvio() {
            return $this->provincia_envio;
        }

        public function getTelefonoEnvio() {
            return $this->telefono_envio;
        }

        public function getUsuarioNombre() {
            return $this->usuarioNombre;
        }

    }

    function obtenerPedidos($con, $pagina, $resultadosPP, $orden, $tipoOrden, $soloUsuario = null) {

        $inicio = ($pagina - 1) * $resultadosPP;

        // Columnas permitidas para ordenar
        $columnasPermitidas = ["p.id", "p.fecha", "p.total", "p.estado", "u.nombre"];
        if (!in_array($orden, $columnasPermitidas)) $orden = "p.id";
        $tipoOrden = strtoupper($tipoOrden) === "DESC" ? "DESC" : "ASC";

        $filtroUsuario = "";
        if ($soloUsuario !== null) {
            $filtroUsuario = "WHERE p.id_usuario = :idUsuario";
        }

        $sql = $con->prepare("SELECT p.id, p.id_usuario, p.fecha, p.total, p.estado, p.tipo_pago, p.direccion_envio, p.localidad_envio, p.provincia_envio, 
                              p.telefono_envio, u.nombre AS usuarioNombre FROM pedidos p JOIN usuarios u ON p.id_usuario = u.id $filtroUsuario 
                              ORDER BY $orden $tipoOrden LIMIT :inicio, :resultados");

        if ($soloUsuario !== null) {
            $sql->bindValue(":idUsuario", $soloUsuario, PDO::PARAM_INT);
        }

        $sql->bindValue(":inicio", $inicio, PDO::PARAM_INT);
        $sql->bindValue(":resultados", $resultadosPP, PDO::PARAM_INT);
        $sql->execute();

        $sql->setFetchMode(PDO::FETCH_CLASS, "Pedido");
        return $sql->fetchAll();
    }

?>