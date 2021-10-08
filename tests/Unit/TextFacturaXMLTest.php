<?php

namespace Tests\Unit;

use App\Models\Factura;
use Tests\TestCase;

class TextFacturaXML extends TestCase
{
    /**
     * Crear una factura y obtener el texto XML
     * @return void
     */
    public function testCrearFactura_xml()
    {
        $factura = new Factura();
        $factura->ambiente = 1;
        $factura->razon_social = 'Empresa de prueba S.A.';
        $factura->nombre_comercial = 'Mi empresa';
        $factura->ruc = '0850539479001';
        $factura->establecimiento = '001';
        $factura->punto_emision = '001';
        $factura->secuencial = '000000160';
        $factura->direccion_matriz = 'Jr. Prueba 123';
        $factura->fecha_emision = now()->format('d/m/Y');
        $factura->dir_establecimiento = 'Jr. Establecimiento 123';
        $factura->contribuyente_especial = '0850539479001';
        $factura->obligado_contabilidad = 'SI';
        $factura->tipo_identificacion_comprador = '05';
        $factura->razon_social_comprador = 'Cliente de prueba S.A.';
        $factura->identificacion_comprador = '1309448197';
        $factura->direccion_comprador = 'Jr. Cliente 123';
        $factura->total_sin_impuestos = '123.45';
        $factura->total_descuento = '0.00';
        $factura->propina = '0.00';
        $factura->importe_total = '138.26';
        $factura->moneda = 'DOLAR';
        $factura->total = '138.26';

        //print($factura->getXML());
        $factura->saveXMLToFile();
        //$factura->sendXML();

        $this->assertTrue(true);
    }
}
