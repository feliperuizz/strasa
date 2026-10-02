<?php

namespace App\Database;

/** Conexão MariaDB que manda datas das consultas em UTC (ver ConvertsDatesToUtc). */
class MariaDbConnection extends \Illuminate\Database\MariaDbConnection
{
    use ConvertsDatesToUtc;
}
