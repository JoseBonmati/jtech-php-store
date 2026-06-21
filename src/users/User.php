<?php

    // Clase entidad Usuario
    class Usuario {

        private $id;
        private $nombre;
        private $email;
        private $contrasenya;
        private $telefono;
        private $direccion;
        private $localidad;
        private $provincia;
        private $rol;
        private $estado;

        public function getId() {
            return $this->id;
        }

        public function getNombre() {
            return $this->nombre;
        }

        public function getEmail() {
            return $this->email;
        }

        public function getContrasenya() {
            return $this->contrasenya;
        }

        public function getTelefono() {
            return $this->telefono;
        }

        public function getDireccion() {
            return $this->direccion;
        }

        public function getLocalidad() {
            return $this->localidad;
        }

        public function getProvincia() {
            return $this->provincia;
        }

        public function getRol() {
            return $this->rol;
        }

        public function getEstado() {
            return $this->estado;
        }
    }

    // Obtener categorías con paginación y ordenación
    function obtenerUsuarios($con, $pagina, $resultadosPP, $orden, $tipoOrden) {

        $inicio = ($pagina - 1) * $resultadosPP;

        // Columnas permitidas para ordenar
        $columnasPermitidas = ["id", "nombre", "email", "telefono", "rol", "estado"];
        if (!in_array($orden, $columnasPermitidas)) $orden = "id";
        $tipoOrden = strtoupper($tipoOrden) === "DESC" ? "DESC" : "ASC";

        $sql = $con->prepare("SELECT id, nombre, email, contrasenya, telefono, direccion, localidad, provincia, rol, estado FROM usuarios
                              ORDER BY $orden $tipoOrden LIMIT :inicio, :resultados");

        $sql->bindParam(":inicio", $inicio, PDO::PARAM_INT);
        $sql->bindParam(":resultados", $resultadosPP, PDO::PARAM_INT);
        $sql->execute();

        // Mapear resultados a la clase Usuario
        $sql->setFetchMode(PDO::FETCH_CLASS, "Usuario");
        return $sql->fetchAll();
    }

?>