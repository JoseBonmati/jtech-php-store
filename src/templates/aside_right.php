    <aside class="sidebar-right">
        <div class="aside-content">

            <?php

                if (isset($_GET["created_name"]) && isset($_GET["created_email"])) {
                    $createdName = htmlspecialchars($_GET["created_name"]);
                    $createdEmail = htmlspecialchars($_GET["created_email"]);
                    echo "<p class='alert alert-success'>El usuario <b>$createdName</b> con email <b>$createdEmail</b> se ha creado correctamente.</p>";
                }
                if (isset($_GET["updated_name"]) && isset($_GET["updated_email"])) {
                    $updatedName = htmlspecialchars($_GET["updated_name"]);
                    $updatedEmail = htmlspecialchars($_GET["updated_email"]);
                    echo "<p class='alert alert-success text-center mb-4'>El usuario <b>$updatedName</b> con email <b>$updatedEmail</b> ha sido modificado correctamente.</p>";
                }
                if (isset($_GET["reset_name"])) {
                    $resetName = htmlspecialchars($_GET["reset_name"]);
                    echo "<p class='alert alert-success'>El usuario <b>$resetName</b> ha restablecido su contraseña correctamente.</p>";
                }
                if (isset($_GET["unauthorized_access"])) {
                    echo "<p class='alert alert-danger'>Ha intentado acceder sin un usuario válido, inicie sesión para acceder.</p>";
                }
                if (isset($_GET["account_deactivated"])) {
                    echo "<p class='alert alert-warning'>Tu cuenta ha sido desactivada correctamente.</p>";
                }
                if (isset($_GET["error"])) {
                    $error = htmlspecialchars($_GET["error"]);
                    echo "<p class='alert alert-danger'>$error</p>";
                }

            ?>

            <?php if (!isset($_SESSION["id"])): ?>
                <h4 class="text-center mb-3 fw-bold">Iniciar sesión</h4>

                <form action="/users/login.php" method="post">
                    <div class="mb-3">
                        <label class="form-label">Correo electrónico</label>
                        <input type="text" name="email" class="form-control jtech-input">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="password" class="form-control jtech-input">
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="login_submit" class="btn btn-jtech fw-semibold">Entrar</button>
                        <hr>
                        <a href="/users/user_create.php" class="btn btn-outline-jtech fw-semibold">Registrarse</a>
                    </div>

                    <div class="text-center mt-3">
                        <p class="mb-1">¿Has olvidado tu contraseña?</p>
                        <a href="/users/user_reset_password.php" class="text-jtech fw-semibold">Restablecer contraseña</a>
                    </div>
                </form>

            <?php else: ?>
                <h4 class="text-center mb-3 fw-bold">Mi cuenta</h4>

                <p class="text-center">Bienvenido/a <?= htmlspecialchars($_SESSION["nombre"]) ?></p>

                <div class="d-grid gap-2 mt-3">
                    <?php if ($_SESSION["rol"] === "administrador"): ?>
                        <a href="/users/user_edit.php?only_mine=1&id=<?= $_SESSION["id"] ?>" class="btn btn-jtech fw-semibold">Editar mis datos</a>
                    <?php else: ?>
                        <a href="/users/user_edit.php?id=<?= $_SESSION["id"] ?>" class="btn btn-jtech fw-semibold">Editar mis datos</a>
                    <?php endif; ?>

                    <?php if ($_SESSION["rol"] === "empleado" || $_SESSION["rol"] === "administrador"): ?>
                        <a href="/orders/order_list.php?only_mine=1" class="btn btn-outline-jtech fw-semibold">Mis pedidos</a>
                    <?php else: ?>
                        <a href="/orders/order_list.php" class="btn btn-outline-jtech fw-semibold">Mis pedidos</a>
                    <?php endif; ?>

                    <?php if ($_SESSION["rol"] === "empleado" || $_SESSION["rol"] === "administrador"): ?>
                        <a href="/utils/admin_panel.php" class="btn btn-outline-success fw-semibold">Panel de gestión</a>
                    <?php endif; ?>

                    <hr>
                    
                    <a href="/users/logout.php" class="btn btn-outline-danger fw-semibold">Cerrar sesión</a>

                    <?php if ($_SESSION["rol"] !== "administrador"): ?>
                        <a href="/users/user_deactivate.php?id=<?= $_SESSION["id"] ?>&action=deactivate" class="btn btn-outline-warning fw-semibold"
                        onclick="return confirm('¿Seguro que deseas desactivar tu cuenta?');">Desactivar mi cuenta</a>
                    <?php endif; ?>
                </div>

            <?php endif; ?>
            
        </div>
    </aside>