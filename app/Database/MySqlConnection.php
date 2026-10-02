<?php

namespace App\Database;

/** Conexão MySQL que manda datas das consultas em UTC (ver ConvertsDatesToUtc). */
class MySqlConnection extends \Illuminate\Database\MySqlConnection
{
    use ConvertsDatesToUtc;
}
