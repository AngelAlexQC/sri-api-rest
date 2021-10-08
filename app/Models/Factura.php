<?php

namespace App\Models;

use Carbon\Carbon;
use DOMDocument;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use RobRichards\XMLSecLibs\XMLSecurityDSig;


class Factura extends Model
{
    use HasFactory;

    /**
     * Retornar la factura en formato XML
     * @return string
     */
    public function getXML()
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        $factura = $xml->createElement('factura');
        $factura->setAttribute('id', 'comprobante');
        $factura->setAttribute('version', '2.1.0');
        // Información Tributaria
        $infoTributaria = $xml->createElement('infoTributaria');
        $infoTributaria->appendChild($xml->createElement('ambiente', $this->ambiente));
        $infoTributaria->appendChild($xml->createElement('tipoEmision', 1));
        $infoTributaria->appendChild($xml->createElement('razonSocial', $this->razon_social));
        $infoTributaria->appendChild($xml->createElement('nombreComercial', $this->nombre_comercial));
        $infoTributaria->appendChild($xml->createElement('ruc', $this->ruc));
        $infoTributaria->appendChild($xml->createElement('claveAcceso', $this->getClaveAcceso()));
        $infoTributaria->appendChild($xml->createElement('codDoc', '01'));
        $infoTributaria->appendChild($xml->createElement('estab', $this->establecimiento));
        $infoTributaria->appendChild($xml->createElement('ptoEmi', $this->punto_emision));
        $infoTributaria->appendChild($xml->createElement('secuencial', $this->secuencial ? $this->secuencial : '000000001'));
        $infoTributaria->appendChild($xml->createElement('dirMatriz', $this->direccion_matriz));

        // Información de la factura
        $infoFactura = $xml->createElement('infoFactura');
        $infoFactura->appendChild($xml->createElement(
            'fechaEmision',
            $this->fecha_emision ? $this->fecha_emision : now()->format('d/m/Y')
        ));
        $infoFactura->appendChild($xml->createElement('dirEstablecimiento', $this->dir_establecimiento));
        $infoFactura->appendChild($xml->createElement('contribuyenteEspecial', $this->contribuyente_especial));
        $infoFactura->appendChild($xml->createElement('obligadoContabilidad', $this->obligado_contabilidad));
        $infoFactura->appendChild($xml->createElement('tipoIdentificacionComprador', $this->tipo_identificacion_comprador));
        $infoFactura->appendChild($xml->createElement('razonSocialComprador', $this->razon_social_comprador));
        $infoFactura->appendChild($xml->createElement('identificacionComprador', $this->identificacion_comprador));
        $infoFactura->appendChild($xml->createElement('totalSinImpuestos', $this->total_sin_impuestos));
        $infoFactura->appendChild($xml->createElement('totalDescuento', $this->total_descuento));
        $totalConImpuestos = $xml->createElement('totalConImpuestos');
        $totalImpuesto = $xml->createElement('totalImpuesto');
        $totalImpuesto->appendChild($xml->createElement('codigo', 2));
        $totalImpuesto->appendChild($xml->createElement('codigoPorcentaje', 2));
        $totalImpuesto->appendChild($xml->createElement('baseImponible', '123.45'));
        $totalImpuesto->appendChild($xml->createElement('valor', '14.81'));
        $totalConImpuestos->appendChild($totalImpuesto);
        $infoFactura->appendChild($totalConImpuestos);
        $infoFactura->appendChild($xml->createElement('propina', $this->propina));
        $infoFactura->appendChild($xml->createElement('importeTotal', $this->importe_total));
        $infoFactura->appendChild($xml->createElement('moneda', $this->moneda));

        // Pagos
        $pagos = $xml->createElement('pagos');
        $pago = $xml->createElement('pago');
        $pago->appendChild($xml->createElement('formaPago', '01'));
        $pago->appendChild($xml->createElement('total', $this->total));
        $pago->appendChild($xml->createElement('plazo', '30'));
        $pago->appendChild($xml->createElement('unidadTiempo', 'dias'));
        $pagos->appendChild($pago);
        $infoFactura->appendChild($pagos);

        // Detalles
        $detalles = $xml->createElement('detalles');
        $arrayDetalles = [
            [
                'codigoPrincipal' => 'COD001',
                'descripcion' => 'PROD001',
                'cantidad' => '1',
                'precioUnitario' => '123.45',
                'descuento' => '0.00',
                'precioTotalSinImpuesto' => '123.45',
                'impuestos' => [
                    [
                        'codigo' => '2',
                        'codigoPorcentaje' => '2',
                        'tarifa' => '12',
                        'baseImponible' => '123.45',
                        'valor' => '14.81',
                    ],
                ],
            ],
        ];

        foreach ($arrayDetalles as $detalle) {
            $detalleXML = $xml->createElement('detalle');
            $detalleXML->appendChild($xml->createElement('codigoPrincipal', $detalle['codigoPrincipal']));
            $detalleXML->appendChild($xml->createElement('descripcion', $detalle['descripcion']));
            $detalleXML->appendChild($xml->createElement('cantidad', $detalle['cantidad']));
            $detalleXML->appendChild($xml->createElement('precioUnitario', $detalle['precioUnitario']));
            $detalleXML->appendChild($xml->createElement('descuento', $detalle['descuento']));
            $detalleXML->appendChild($xml->createElement('precioTotalSinImpuesto', $detalle['precioTotalSinImpuesto']));
            $impuestos = $xml->createElement('impuestos');
            foreach ($detalle['impuestos'] as $impuesto) {
                $impuestoXML = $xml->createElement('impuesto');
                $impuestoXML->appendChild($xml->createElement('codigo', $impuesto['codigo']));
                $impuestoXML->appendChild($xml->createElement('codigoPorcentaje', $impuesto['codigoPorcentaje']));
                $impuestoXML->appendChild($xml->createElement('tarifa', $impuesto['tarifa']));
                $impuestoXML->appendChild($xml->createElement('baseImponible', $impuesto['baseImponible']));
                $impuestoXML->appendChild($xml->createElement('valor', $impuesto['valor']));
                $impuestos->appendChild($impuestoXML);
            }
            $detalleXML->appendChild($impuestos);
            $detalles->appendChild($detalleXML);
        }

        // Info Adicional
        $infoAdicional = $xml->createElement('infoAdicional');
        $vendedor = $xml->createElement('campoAdicional', 'Ángel Quiroz');
        $vendedor->setAttribute('nombre', 'Vendedor');
        $caja = $xml->createElement('campoAdicional', 'Caja 1');
        $caja->setAttribute('nombre', 'Caja');
        $infoAdicional->appendChild($vendedor);
        $infoAdicional->appendChild($caja);


        $factura->appendChild($infoTributaria);
        $factura->appendChild($infoFactura);
        $factura->appendChild($detalles);
        $factura->appendChild($infoAdicional);
        $xml->appendChild($factura);

        return $xml->saveXML();
    }

    public function getEstructura()
    {
        $fecha = now()->format('dmY');
        $tipo_comprobante = '01';
        $ruc = $this->ruc;
        $tipo_ambiente = '1';
        $serie = $this->establecimiento  . $this->punto_emision;
        $secuencial = $this->secuencial;
        $codigo_numerico = '12345678';
        $tipo_emision = '1';
        return $fecha . $tipo_comprobante . $ruc . $tipo_ambiente . $serie . $secuencial . $codigo_numerico . $tipo_emision;
    }

    /**
     * Generar Clave de Acceso
     * @return string
     */
    public function getClaveAcceso()
    {
        return $this->getEstructura() . $this->getDigitoVerificador();
    }

    public function getDigitoVerificador()
    {
        $estructura = $this->getEstructura();
        if (strlen($estructura) == 48) {
            $digits = str_replace(array('.', ','), array('' . ''), strrev($estructura));
            if (!ctype_digit($digits)) {
                return false;
            }
            $sum = 0;
            $factor = 2;
            for ($i = 0; $i < strlen($digits); $i++) {
                $sum += substr($digits, $i, 1) * $factor;
                if ($factor == 7) {
                    $factor = 2;
                } else {
                    $factor++;
                }
            }
            $dv = 11 - ($sum % 11);
            if ($dv == 10) {
                return 1;
            }
            if ($dv == 11) {
                return 0;
            }
            return $dv;
        }
    }


    /**
     * Guarda el XML en un archivo
     * @return void
     */
    public function saveXMLToFile()
    {
        $xml = $this->getXML();
        $file = fopen('xml' . DIRECTORY_SEPARATOR . $this->getClaveAcceso() . '.xml', 'w');
        fwrite($file, $xml);
        fclose($file);
    }

    /**
     * Firma el archivo XML
     * @return void
     */
    public function signXML()
    {
        // Load the XML to be signed
        $doc = new DOMDocument();
        $doc->load('xml/factura' . $this->secuencial . '.xml');

        // Create a new XMLSignature
        $xml = new XMLSecurityDSig();

        // Create a new Security object
        $objDSig = new XMLSecurityDSig();
        // Use the c14n exclusive canonicalization
        $objDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);
        // Sign using SHA-256
        $objDSig->addReference(
            $doc,
            XMLSecurityDSig::SHA256,
            array('http://www.w3.org/2000/09/xmldsig#enveloped-signature')
        );
    }

    /**
     * Envia el XML al SRI
     * @return string
     */
    public function sendXML()
    {
        $url = 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl';
        $file = file_get_contents('xml/factura' . $this->secuencial . '.xml');
        $xml_envio = "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:ec=\"http://ec.gob.sri.ws.recepcion\">";
        $xml_envio .= "<soapenv:Header/>";
        $xml_envio .= "<soapenv:Body>";
        $xml_envio .= "<ec:validarComprobante>";
        $xml_envio .= "<xml>" . base64_encode($file) . "</xml>";
        $xml_envio .= "</ec:validarComprobante>";
        $xml_envio .= "</soapenv:Body>";
        $xml_envio .= "</soapenv:Envelope>";

        $headers = array(
            "Content-type: text/xml;charset=\"utf-8\"",
            "Accept: text/xml",
        );

        // Envio de la petición
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_envio);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($ch);
        print_r($result);
        curl_close($ch);
        return $result;
    }
}
