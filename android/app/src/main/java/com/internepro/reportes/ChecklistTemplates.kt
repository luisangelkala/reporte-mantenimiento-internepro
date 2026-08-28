package com.internepro.reportes

data class ChecklistItem(val key: String, val label: String)
data class ChecklistSection(val key: String, val title: String, val observationKey: String?, val items: List<ChecklistItem>)

object ChecklistTemplates {
    private fun section(prefix: String, number: Int, title: String, observationKey: String? = null, vararg labels: String): ChecklistSection =
        ChecklistSection("${prefix}_$number", title, observationKey, labels.mapIndexed { index, label -> ChecklistItem("${prefix}_${number}_${('a'.code + index).toChar()}", label) })

    fun forType(type: String): List<ChecklistSection> = if (type == "alimak") alimak() else elevador()

    private fun elevador() = listOf(
        section("s", 0, "INSTRUCCIONES GENERALES", null, "Comportamiento del ascensor informado por la persona a cargo", "Funcionamiento: aceleracion, desaceleracion, vibracion y ruido", "Inspeccion general en condiciones de operacion"),
        section("s", 1, "CUARTO DE MAQUINAS", "ob_1", "Iluminacion", "Senalizacion", "Tapa de ductos", "Tapas de pases de cable", "Climatizacion", "Filtraciones", "Pintura"),
        section("s", 2, "MAQUINA Y FRENO", "ob_2", "Ruido", "Vibraciones", "Conexiones flojas", "Desgaste de la zapata del freno", "Frenado de emergencia", "Nivel de aceite"),
        section("s", 3, "GOBERNADOR Y CABLE", "ob_3", "Ruido", "Switcher", "Cable", "Sello de fabrica", "Velocidad de disparo m/s"),
        section("s", 4, "TERMINALES DE CABLES", "ob_4", "Perros", "Tuercas", "Pasapuntas", "Quitavueltas"),
        section("s", 5, "CABINA", "ob_5", "Alarma", "Interfon", "Iluminacion", "Piso", "Falso techo y paneles flojos", "Abanicos", "Display", "Botones"),
        section("s", 6, "PUERTA DE CABINA", "ob_6", "Operador de puerta", "Correas o cables", "Ruedas y contrarruedas", "Zapatos", "Switch", "Fotocelda", "Velocidad", "Botones", "Ruido"),
        section("s", 7, "SOBRE CABINA", "ob_7", "Switch del paracaida", "Limites de recorrido", "Inductores", "Pesacarga", "Caja de conexiones", "Tarjeta de comunicacion"),
        section("s", 8, "SOBRE CABINA", "ob_8", "Baranda de proteccion", "Cover de la polea", "Polea", "Terminales de cables", "Tuercas y pasapuntas", "Zapatos de cabina y contrapeso", "Aceiteras"),
        section("s", 9, "SOBRE CABINA", "ob_9", "Estado de rieles", "Empates", "Brackets", "Clip", "Lubricacion"),
        section("s", 10, "BAJO CABINA", "ob_10", "Bloque seguridad", "Cadena de compensacion", "Tensores"),
        section("s", 11, "BAJO CABINA", "ob_11", "Estado del marco rotura", "Oxidacion", "Zapatos de cabina", "Pintura", "Corrosion"),
        section("s", 12, "PIT", "ob_12", "Buffer", "Pesa del paracaidas", "Iluminacion", "Switcher", "Limite"),
        section("s", 13, "PUERTAS DE PASILLO", "ob_13", "Ruedas y contrarruedas", "Zapatos", "Switches Sill", "Pintura", "Limpieza y lubricacion", "Corrosion"),
        section("s", 14, "PASILLO", "ob_14", "Interfono", "Bombero", "Displays", "Botoneras", "Tarjetas de comunicacion"),
        section("s", 15, "CALIDAD DE RECORRIDO", "ob_15", "Ruidos", "Golpes", "Movimientos", "Nivel de parada")
    )

    private fun alimak() = listOf(
        section("a", 0, "INSTRUCCIONES GENERALES", null, "Comportamiento del equipo informado por la persona a cargo", "Funcionamiento: aceleracion, desaceleracion, vibracion y ruido", "Inspeccion general en condiciones de operacion"),
        section("a", 1, "CUARTO DE MAQUINAS", "ab_1", "Informacion de instrucciones y de seguridad escalera"),
        section("a", 2, "CABINA", "ab_2", "Estado de los paneles de la cabina: limpieza y golpes"),
        section("a", 3, "PUERTAS", "ab_3", "Puerta de cabina", "Puerta de escotilla", "Interlocks", "Switch actuador", "Actuador", "Switch de puerta"),
        section("a", 4, "CABINA", "ab_4", "Luz de cabina", "Abanicos", "Alarma", "Display", "Stop", "Intercom", "Botones de llamada"),
        section("a", 5, "LIMITES", "ab_5", "Limit switch ref", "SW Final", "SW up dw"),
        section("a", 6, "INSPECCION", "ab_6", "Caja de inspeccion", "Stop", "Switch inspeccion", "SW emergencia", "Boton up", "Dw"),
        section("a", 7, "PANEL", "ab_7", "Estado de panel", "Tapa de regletas", "Estado cable viajero"),
        section("a", 8, "PANEL BASE", "ab_8", "Estado panel Base", "ACL Base", "Switch"),
        section("a", 9, "CONTROL", "ab_9", "Contactores", "Auxiliares", "Breaker", "Relay", "Temporizadores", "Conexiones", "ACL", "Tarjeta com"),
        section("a", 10, "ACEITE", "ab_10", "Nivel de aceite", "Temperatura", "Filtro"),
        section("a", 11, "MAQUINA", "ab_11", "Ruidos", "Vibraciones", "Pintura"),
        section("a", 12, "FRENO", "ab_12", "Freno magnetico", "Desgaste", "Parada"),
        section("a", 13, "EMERGENCIA", "ab_13", "Recorrido en parada de emergencia"),
        section("a", 14, "MOTOR", "ab_14", "Moto electrico", "Bloque conex"),
        section("a", 15, "CREMALLERA", "ab_15", "Pinon", "Cremallera", "Contrarueda"),
        section("a", 16, "GUIA", "ab_16", "Roller guide conjunto maquina"),
        section("a", 17, "GUIA", "ab_17", "Roller guide cabina"),
        section("a", 18, "CABINA", "ab_18", "Soportes de cabina"),
        section("a", 19, "FRENO CENTRIFUGO", "ab_19", "Freno centrifugo", "Cables", "Resortes", "Varillas", "Ajuste", "Coopling monitor"),
        section("a", 20, "SEGURIDAD", "ab_20", "Bloque seguridad", "Contrarueda"),
        section("a", 21, "DOCUMENTACION", "ab_21", "Fecha de vencimiento"),
        section("a", 22, "PARACAIDAS", "ab_22", "Prueba paracaida fecha"),
        section("a", 23, "CREMALLERA", "ab_23", "Estado de la cremallera alineacion"),
        section("a", 24, "MASTIL", "ab_24", "Ajuste del mastil: tornillos, tuercas, apriete y fijacion"),
        section("a", 25, "CABLE VIAJERO", "ab_25", "Estado del cable viajero"),
        section("a", 26, "CABLE VIAJERO", "ab_26", "Soporte del cable viajero y guias"),
        section("a", 27, "PUERTA", "ab_27", "Mecanismo de la puerta", "Cam", "Bisagras", "Lock flap", "Switch pasillo"),
        section("a", 28, "PUERTAS DE PASILLO", "ab_28", "Puertas de pasillo: estado y limpieza"),
        section("a", 29, "BUFFER", "ab_29", "Buffer superior"),
        section("a", 30, "PARADAS", "ab_30", "Ajuste de camas de paradas y banderas"),
        section("a", 31, "BOTONERAS", "ab_31", "Botoneras de pasillos", "Botones", "Stop"),
        section("a", 32, "FOSO", "ab_32", "Stop de foso"),
        section("a", 33, "BUFFER", "ab_33", "Buffer"),
        section("a", 34, "CABINA", "ab_34", "Estado del marco de cabina"),
        section("a", 35, "TROLLEY", "ab_35", "Rolley guias trolley", "Rolley de cable"),
        section("a", 36, "TROLLEY", "ab_36", "Distancia del trolley de la base"),
        section("a", 37, "TROLLEY", "ab_37", "Cremallera", "Roller", "Puertas", "Inter lock", "Dispositivo de seguridad", "Mecanismos freno centrifugo", "Trolley")
    )
}
