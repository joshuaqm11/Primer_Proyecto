<?php

/**
 * PHPMailer Exception class.
 * PHP Version 5.5.
 *
 * @see       https://github.com/PHPMailer/PHPMailer/ The PHPMailer GitHub project
 *
 * @author    Marcus Bointon (Synchro/coolbru) <phpmailer@synchromedia.co.uk>
 * @author    Jim Jagielski (jimjag) <jimjag@gmail.com>
 * @author    Andy Prevost (codeworxtech) <codeworxtech@users.sourceforge.net>
 * @author    Brent R. Matzelle (original founder)
 * @copyright 2012 - 2020 Marcus Bointon
 * @copyright 2010 - 2012 Jim Jagielski
 * @copyright 2004 - 2009 Andy Prevost
 * @license   https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html GNU Lesser General Public License
 * @note      Este archivo forma parte de la librería de terceros PHPMailer.
 *            No modificar su lógica; solo se agregan comentarios explicativos.
 *            Provee una clase de excepción específica para PHPMailer.
 */

namespace PHPMailer\PHPMailer;

/**
 * PHPMailer exception handler.
 * Clase de excepción propia de PHPMailer que extiende \Exception.
 * Permite formatear mensajes de error en HTML a través de errorMessage().
 */
class Exception extends \Exception
{
    /**
     * Prettify error message output.
     * Devuelve el mensaje de error con un formateo HTML básico.
     *
     * @return string Mensaje HTML seguro para salida en páginas
     */
    public function errorMessage()
    {
        // htmlspecialchars evita inyección de HTML al mostrar el mensaje
        return '<strong>' . htmlspecialchars($this->getMessage(), ENT_COMPAT | ENT_HTML401) . "</strong><br />\n";
    }
}
