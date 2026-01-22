<?php
require_once __DIR__ . '/../config/db.php';

class LogisticaHelper {
    
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Busca o Crea una sucursal basada en datos sucios del PDF.
     * Si encuentra una EXACTA, la devuelve.
     * Si no, crea una nueva en estado 'pendiente' para revisión humana.
     */
    public function procesarSucursal($idCliente, $calle, $altura, $localidad, $horarios) {
        // Limpieza básica
        $calle = trim($calle ?? '');
        $altura = trim($altura ?? '');
        $localidad = trim($localidad ?? '');
        $textoCompleto = trim("$calle $altura, $localidad");

        // 1. Intentar buscar coincidencia EXACTA en dirección y cliente
        // (Solo si tenemos altura, para evitar falsos positivos con direcciones vacías)
        if (!empty($altura)) {
            $sql = "SELECT id_sucursal FROM sucursales 
                    WHERE id_cliente = ? 
                    AND calle_pdf = ? 
                    AND altura_pdf = ? 
                    LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$idCliente, $calle, $altura]);
            $existente = $stmt->fetch();

            if ($existente) {
                return $existente['id_sucursal'];
            }
        }

        // 2. Si no existe exacta, insertamos una NUEVA como 'pendiente'
        $sqlInsert = "INSERT INTO sucursales 
                      (id_cliente, calle_pdf, altura_pdf, localidad_pdf, texto_busqueda, dias_horarios, estado_direccion) 
                      VALUES (?, ?, ?, ?, ?, ?, 'pendiente')";
        $stmtInsert = $this->pdo->prepare($sqlInsert);
        $stmtInsert->execute([$idCliente, $calle, $altura, $localidad, $textoCompleto, $horarios]);
        
        return $this->pdo->lastInsertId();
    }

    /**
     * Función para el Panel Humano: Sugiere direcciones ya verificadas
     * que se parecen a la dirección sucia.
     */
    public function obtenerSugerencias($textoSucio, $idCliente) {
        // Obtenemos direcciones VERIFICADAS de este cliente
        $sql = "SELECT id_sucursal, direccion_formateada, calle_pdf, altura_pdf 
                FROM sucursales 
                WHERE id_cliente = ? AND estado_direccion = 'verificada'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$idCliente]);
        $verificadas = $stmt->fetchAll();

        $sugerencias = [];
        
        foreach ($verificadas as $suc) {
            $similitud = 0;
            // Compara el texto sucio con la calle original guardada
            $textoComparar = $suc['calle_pdf'] . ' ' . $suc['altura_pdf'];
            similar_text(strtoupper($textoSucio), strtoupper($textoComparar), $similitud);

            // Si se parece más del 70%, es una sugerencia fuerte
            if ($similitud > 70) {
                $suc['porcentaje'] = round($similitud, 2);
                $sugerencias[] = $suc;
            }
        }

        // Ordenar por similitud
        usort($sugerencias, function($a, $b) {
            return $b['porcentaje'] <=> $a['porcentaje'];
        });

        return $sugerencias;
    }
}
?>