<?php

if (!empty($errores)): ?>
    <div class="alerta alerta--error">
        <?php foreach ($errores as $e): ?>
            <p><?= $e ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>