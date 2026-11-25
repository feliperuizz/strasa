<?php

namespace App\Video\Claude;

use RuntimeException;

/** A assinatura do Claude bateu no limite de uso. A edição volta para a fila mais tarde. */
class LimiteDoClaude extends RuntimeException {}
