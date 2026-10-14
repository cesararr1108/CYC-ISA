<?php
class Factura
{
    public Seguridad $seguridad;
    public Documento $documento;
    public Cliente $cliente;
    public FormaPago $formaPago;
    public array $items = [];
    public array $impuestosTotales = [];
    public array $retencionesTotales = [];

    public function __construct()
    {
        $this->seguridad = new Seguridad();
        $this->documento = new Documento();
        $this->cliente = new Cliente();
        $this->formaPago = new FormaPago();
    }

    public function agregarItem(Item $item)
    {
        $this->items[] = $item;
    }

    public function agregarImpuesto(SubtotalImpuesto $imp)
    {
        $this->impuestosTotales[] = $imp;
    }
}

class Seguridad
{
    public string $tokenUser = '';
    public string $tokenPassword = '';
}

class Documento
{
    public string $tipo = '';
    public string $numero = '';
    public string $tipoE = '';
    public string $fecha = '';
    public string $tipoED = '';
}

class Cliente
{
    public string $nombres = '';
    public string $razon = '';
    public string $nit = '';
    public string $digito = '';
    public string $tipo_persona = '';
    public string $responsabilidades = '';
    public Direccion $direccion;
    public Contacto $contacto;

    public function __construct()
    {
        $this->direccion = new Direccion();
        $this->contacto = new Contacto();
    }
}

class Direccion
{
    public string $codigo_ciudad = '';
    public string $nombre_ciudad = '';
    public string $codigo_postal = '';
    public string $nombre_dpto = '';
    public string $codigo_dpto = '';
    public string $direccion = '';
    public Pais $pais;

    public function __construct()
    {
        $this->pais = new Pais();
    }
}

class Pais
{
    public string $codigo_pais = '';
    public string $nombre_pais = '';
}

class Contacto
{
    public string $telefono = '';
    public string $email = '';
}

class FormaPago
{
    public string $id = '';
    public string $codigo = '';
    public string $fecha = '';
    public string $diasValidez = '';
}

class Item
{
    public string $codigo = '';
    public string $descripcion = '';
    public bool $muestraGratis = false;
    public float $cantidad = 0;
    public float $pcjImpuesto1 = 0;
    public float $pcjImpuesto3 = 0;
    public float $pcjImpuesto4 = 0;
    public float $valorUnitario = 0;
    public float $pcjDescuento = 0;
    public string $lote = '';
    public string $vmcto = '';
    public  SubtotalImpuesto $impuestoTotal;
}

class EsquemaImpuesto
{
    public string $id;
    public string $nombre;

    public function __construct(string $id, string $nombre)
    {
        $this->id = $id;
        $this->nombre = $nombre;
    }
}

class CategoriaImpuesto
{
    public float $porcentaje;
    public EsquemaImpuesto $esquemaImpuesto;

    public function __construct(float $porcentaje, EsquemaImpuesto $esquemaImpuesto)
    {
        $this->porcentaje = $porcentaje;
        $this->esquemaImpuesto = $esquemaImpuesto;
    }
}

class SubtotalImpuesto
{
    public float $montoBase;
    public float $montoImpuesto;
    public CategoriaImpuesto $categoriaImpuesto;

    public function __construct(float $montoBase, float $montoImpuesto, CategoriaImpuesto $categoriaImpuesto)
    {
        $this->montoBase = $montoBase;
        $this->montoImpuesto = $montoImpuesto;
        $this->categoriaImpuesto = $categoriaImpuesto;
    }
}

class TotalImpuesto
{
    public float $montoImpuesto;
    public float $montoRedondeo;
    public SubtotalImpuesto $subtotalImpuesto;

    public function __construct(float $montoImpuesto, float $montoRedondeo, SubtotalImpuesto $subtotalImpuesto)
    {
        $this->montoImpuesto = $montoImpuesto;
        $this->montoRedondeo = $montoRedondeo;
        $this->subtotalImpuesto = $subtotalImpuesto;
    }
}