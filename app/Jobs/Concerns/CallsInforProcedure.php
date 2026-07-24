<?php

namespace App\Jobs\Concerns;

use Exception;

trait CallsInforProcedure
{
    /**
     * Ejecuta un procedimiento de Infor vía ODBC.
     */
    protected function callInforProcedure(string $procedure): void
    {
        $dsn = "Driver={Client Access ODBC Driver (32-bit)};System=192.168.200.7;Uid=LXSECOFR;Pwd=LXSECOFR";

        $conn = odbc_connect($dsn, "", "");

        if (!$conn) {
            throw new Exception("Fallo de conexión ODBC: " . odbc_errormsg());
        }

        $result = @odbc_exec($conn, "CALL {$procedure}");

        if (!$result) {
            $error = odbc_errormsg($conn);
            odbc_close($conn);
            throw new Exception("Error en el procedimiento Infor: " . $error);
        }

        odbc_close($conn);
    }
}
