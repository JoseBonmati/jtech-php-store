
    <aside class="aside-derecho">
        <div class="aside-content">

            <?php

                if (isset($_GET["nombreN"]) && isset($_GET["emailN"])) {
                    $nombreN = htmlspecialchars($_GET["nombreN"]);
                    $emailN = htmlspecialchars($_GET["emailN"]);
                    echo "<p class='alert alert-success'>El usuario <b>$nombreN</b> con email <b>$emailN</b> se ha creado correctamente.</p>";
                }
                if (isset($_GET["nombreE"]) && isset($_GET["emailE"])) {
                    $nombreE = htmlspecialchars($_GET["nombreE"]);
                    $emailE = htmlspecialchars($_GET["emailE"]);
                    echo "<p class='alert alert-success text-center mb-4'>El usuario <b>$nombreE</b> con email <b>$emailE</b> ha sido modificado correctamente.</p>";
                }
                if (isset($_GET["nameR"])) {
                    $nameR = htmlspecialchars($_GET["nameR"]);
                    echo "<p class='alert alert-success'>El usuario <b>$nameR<b> ha restablecido su contraseña correctamente.</p>";
                }
                if (isset($_GET["acceso"])) {
                    echo "<p class='alert alert-danger'>Ha intentado acceder sin un usuario válido, inicie sesión para acceder.</p>";
                }
                if (isset($_GET["cuentaDesactivada"])) {
                    echo "<p class='alert alert-warning'>Tu cuenta ha sido desactivada correctamente.</p>";
                }
                if (isset($_GET["error"])) {
                    $error = htmlspecialchars($_GET["error"]);
                    echo "<p class='alert alert-danger'>$error</p>";
                }

            ?>

            <?php if (!isset($_SESSION["id"])): ?>
                <h4 class="text-center mb-3 fw-bold">Iniciar sesión</h4>

                <form action="usuarios/login.php" method="post">
                    <div class="mb-3">
                        <label class="form-label">Correo electrónico</label>
                        <input type="text" name="email" class="form-control jtech-input">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="contrasenya" class="form-control jtech-input">
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="iniciarS" class="btn btn-jtech fw-semibold">Entrar</button>
                        <hr>
                        <a href="usuarios/usuarioCrear.php" class="btn btn-outline-jtech fw-semibold">Registrarse</a>
                    </div>

                    <div class="text-center mt-3">
                        <p class="mb-1">¿Has olvidado tu contraseña?</p>
                        <a href="usuarios/restablecerContrasenya.php" class="text-jtech fw-semibold">Restablecer contraseña</a>
                    </div>
                </form>

            <?php else: ?>
                <h4 class="text-center mb-3 fw-bold">Mi cuenta</h4>

                <p class="text-center">Bienvenido/a <?= htmlspecialchars($_SESSION["nombre"]) ?></p>

                <div class="d-grid gap-2 mt-3">
                    <?php if ($_SESSION["rol"] === "administrador"): ?>
                        <a href="usuarios/usuarioEditar.php?soloMios=1&id=<?= $_SESSION["id"] ?>" class="btn btn-jtech fw-semibold">Editar mis datos</a>
                    <?php else: ?>
                        <a href="usuarios/usuarioEditar.php?id=<?= $_SESSION["id"] ?>" class="btn btn-jtech fw-semibold">Editar mis datos</a>
                    <?php endif; ?>

                    <?php if ($_SESSION["rol"] === "empleado" || $_SESSION["rol"] === "administrador"): ?>
                        <a href="pedidos/pedidoConsulta.php?soloMios=1" class="btn btn-outline-jtech fw-semibold">Mis pedidos</a>
                    <?php else: ?>
                        <a href="pedidos/pedidoConsulta.php" class="btn btn-outline-jtech fw-semibold">Mis pedidos</a>
                    <?php endif; ?>

                    <?php if ($_SESSION["rol"] === "empleado" || $_SESSION["rol"] === "administrador"): ?>
                        <a href="utilidades/panelAdministrador.php" class="btn btn-outline-success fw-semibold">Panel de gestión</a>
                    <?php endif; ?>

                    <hr>
                    
                    <a href="usuarios/cerrarSesion.php" class="btn btn-outline-danger fw-semibold">Cerrar sesión</a>

                    <?php if ($_SESSION["rol"] !== "administrador"): ?>
                        <a href="usuarios/usuarioDesactivar.php?id=<?= $_SESSION["id"] ?>&accion=desactivar" class="btn btn-outline-warning fw-semibold"
                        onclick="return confirm('¿Seguro que deseas desactivar tu cuenta?');">Desactivar mi cuenta</a>
                    <?php endif; ?>
                </div>

            <?php endif; ?>
            
        </div>
    </aside>