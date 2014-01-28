/**
 * @package sociallogin
 * @license GNU GENERAL PUBLIC LICENSE Version 2, June 1991
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */
jQuery(document).ready(function (b) {
    var d = {AL: "Alabama", AK: "Alaska", AZ: "Arizona", AR: "Arkansas", CA: "California", CO: "Colorado", CT: "Connecticut", DE: "Delaware",
    DC: "District of Columbia", FL: "Florida", GA: "Georgia", HI: "Hawaii", ID: "Idaho", IL: "Illinois", IN: "Indiana", IA: "Iowa",
    KS: "Kansas", KY: "Kentucky", LA: "Louisiana", ME: "Maine", MD: "Maryland", MA: "Massachusetts", MI: "Michigan", MN: "Minnesota",
    MS: "Mississippi", MO: "Missouri", MT: "Montana", NE: "Nebraska", NV: "Nevada", NH: "New Hampshire", NJ: "New Jersey", NM: "New Mexico",
    NY: "New York", NC: "North Carolina", ND: "North Dakota", OH: "Ohio", OK: "Oklahoma", OR: "Oregon", PA: "Pennsylvania",
    RI: "Rhode Island", SC: "South Carolina", SD: "South Dakota", TN: "Tennessee", TX: "Texas", UT: "Utah", VT: "Vermont", VA: "Virginia",
    WA: "Washington", WV: "West Virginia", WI: "Wisconsin", WY: "Wyoming"}, f = {BC: "British Columbia", ON: "Ontario", NF: "Newfoundland",
    NS: "Nova Scotia", PE: "Prince Edward Island", NB: "New Brunswick", QC: "Quebec", MB: "Manitoba", SK: "Saskatchewan", AB: "Alberta",
    NT: "Northwest Territories", YT: "Yukon Territory"},
g = {AGS: "Aguascalientes", BCN: "Baja California", BCS: "Baja California Sur", CAM: "Campeche", CHP: "Chiapas", CHH: "Chihuahua",
    COA: "Coahuila", COL: "Colima", DIF: "Distrito Federal", DUR: "Durango", GUA: "Guanajuato", GRO: "Guerrero", HID: "Hidalgo",
    JAL: "Jalisco", MEX: "Estado de Mexico", MIC: "Michoacan de Ocampo", MOR: "Morelos", NAY: "Nayarit", NLE: "Nuevo Leon", OAX: "Oaxaca",
    PUE: "Puebla", QUE: "Queretaro de Arteaga", ROO: "Quintana Roo", SLP: "San Luis Potosi", SIN: "Sinaloa", SON: "Sonora", TAB: "Tabasco",
    TAM: "Tamaulipas", TLA: "Tlaxcala",
    VER: "Veracruz-Llave", YUC: "Yucatan", ZAC: "Zacatecas"}, h = {B: "Buenos Aires", K: "Catamarca", H: "Chaco", U: "Chubut",
    C: "Ciudad de Buenos Aires", X: "C\u00f3rdoba", W: "Corrientes", E: "Entre Rios", P: "Formosa", Y: "Jujuy", L: "La Pampa",
    F: "La Rioja", M: "Mendoza", N: "Misiones", Q: "Neuquen", R: "Rio Negro", A: "Salta", J: "San Juan", D: "San Luis", Z: "Santa Cruz",
    S: "Santa Fe", G: "Santiago del Estero", V: "Tierra del Fuego", T: "Tucuman"}, k = {AG: "Agrigento", AL: "Alessandria", AN: "Ancona",
    AO: "Aosta", AR: "Arezzo", AP: "Ascoli Piceno", AT: "Asti",
    AV: "Avellino", BA: "Bari", BT: "Barletta-Andria-Trani", BL: "Belluno", BN: "Benevento", BG: "Bergamo", BI: "Biella", BO: "Bologna",
    BZ: "Bolzano", BS: "Brescia", BR: "Brindisi", CA: "Cagliari", CL: "Caltanissetta", CB: "Campobasso", CI: "Carbonia-Iglesias",
    CE: "Caserta", CT: "Catania", CZ: "Catanzaro", CH: "Chieti", CO: "Como", CS: "Cosenza", CR: "Cremona", KR: "Crotone", CN: "Cuneo",
    EN: "Enna", FM: "Fermo", FE: "Ferrara", FI: "Firenze", FG: "Foggia", FC: "Forl\u00ec-Cesena", FR: "Frosinone", GE: "Genova", GO: "Gorizia",
    GR: "Grosseto", IM: "Imperia", IS: "Isernia",
    AQ: "L'Aquila", SP: "La Spezia", LT: "Latina", LE: "Lecce", LC: "Lecco", LI: "Livorno", LO: "Lodi", LU: "Lucca", MC: "Macerata",
    MN: "Mantova", MS: "Massa", MT: "Matera", VS: "Medio Campidano", ME: "Messina", MT: "Milano", MO: "Modena", MB: "Monza e della Brianza",
    NA: "Napoli", NO: "Novara", NU: "Nuoro", OG: "Ogliastra", OT: "Olbia-Tempio", OR: "Oristano", PD: "Padova", PA: "Palermo", PR: "Parma",
    PV: "Pavia", PG: "Perugia", PU: "Pesaro-Urbino", PE: "Pescara", PC: "Piacenza", PI: "Pisa", PT: "Pistoia", PN: "Pordenone", PZ: "Potenza",
    PO: "Prato", RG: "Ragusa", RA: "Ravenna",
    RC: "Reggio Calabria", RE: "Reggio Emilia", RI: "Rieti", RN: "Rimini", RM: "Roma", RO: "Rovigo", SA: "Salerno", SS: "Sassari",
    SV: "Savona", SI: "Siena", SR: "Siracusa", SO: "Sondrio", TA: "Taranto", TE: "Teramo", TR: "Terni", TO: "Torino", TP: "Trapani",
    TN: "Trento", TV: "Treviso", TS: "Trieste", UD: "Udine", VA: "Varese", VE: "Venezia", VB: "Verbano-Cusio-Ossola", VC: "Vercelli",
    VR: "Verona", VV: "Vibo Valentia", VI: "Vicenza", VT: "Viterbo"}, l = {AC: "Aceh", BA: "Bali", BB: "Bangka", BT: "Banten",
    BE: "Bengkulu", JT: "Central Java", KT: "Central Kalimantan", ST: "Central Sulawesi",
    JI: "Coat of arms of East Java", KI: "East kalimantan", NT: "East Nusa Tenggara", GO: "Lambang propinsi", JK: "Jakarta",
    JA: "Jambi", LA: "Lampung", MA: "Maluku", MU: "North Maluku", SA: "North Sulawesi", SU: "North Sumatra", PA: "Papua",
    RI: "Riau", KR: "Lambang Riau", SG: "Southeast Sulawesi", KS: "South Kalimantan", SN: "South Sulawesi", SS: "South Sumatra",
    JB: "West Java", KB: "West Kalimantan", NB: "West Nusa Tenggara", PB: "Lambang Provinsi Papua Barat", SR: "West Sulawesi",
    SB: "West Sumatra", YO: "Yogyakarta"}, m = {1: "Aichi", 2: "Akita", 3: "Aomori",
    4: "Chiba", 5: "Ehime", 6: "Fukui", 7: "Fukuoka", 8: "Fukushima", 9: "Gifu", 10: "Gumma", 11: "Hiroshima",
    12: "Hokkaido", 13: "Hyogo", 14: "Ibaraki", 15: "Ishikawa", 16: "Iwate", 17: "Kagawa", 18: "Kagoshima", 19: "Kanagawa",
    20: "Kochi", 21: "Kumamoto", 22: "Kyoto", 23: "Mie", 24: "Miyagi", 25: "Miyazaki", 26: "Nagano", 27: "Nagasaki", 28: "Nara",
    29: "Niigata", 30: "Oita", 31: "Okayama", 32: "Osaka", 33: "Saga", 34: "Saitama", 35: "Shiga", 36: "Shimane", 37: "Shizuoka",
    38: "Tochigi", 39: "Tokushima", 40: "Tokyo", 41: "Tottori", 42: "Toyama", 43: "Wakayama", 44: "Yamagata",
    45: "Yamaguchi", 46: "Yamanashi", 47: "Okinawa"}, n = b("#location-country");
n.data("oldval", n.val());
n.change(function () {
    var e = b(this);
    if ("US" == this.value) {
    document.getElementById("location-state-div").style.display = "block";
    var a = '<span class="spantxt">State:</span><select class="inputtxt" name="location-state" id="location-state">';
    b("#location-state-div").html();
    b("#location-state").val();
    for (var c in d)a = void 0 == c ? a + ('<option value="' + c + '" selected="selected">' + d[c] + "</option>") : a +
    ('<option value="' + c + '">' + d[c] + "</option>");
    a += "</select>";
    b("#location-state-div").html(a);
    e.data("oldval", e.val())
    } else if ("CA" == this.value) {
    document.getElementById("location-state-div").style.display = "block";
    a = '<span class="spantxt">State:</span><select class="inputtxt" name="location-state" id="location-state">';
    b("#location-state-div").html();
    b("#location-state").val();
    for (c in f)a = void 0 == c ? a + ('<option value="' + c + '" selected="selected">' + f[c] + "</option>") : a +
    ('<option value="' + c + '">' + f[c] + "</option>");
    a += "</select>";
    b("#location-state-div").html(a);
    e.data("oldval", e.val())
    } else if ("MX" == this.value) {
    document.getElementById("location-state-div").style.display = "block";
    a = '<span class="spantxt">State:</span><select class="inputtxt" name="location-state" id="location-state">';
    b("#location-state-div").html();
    b("#location-state").val();
    for (c in g)a = void 0 == c ? a + ('<option value="' + c + '" selected="selected">' + g[c] + "</option>") : a +
    ('<option value="' + c + '">' + g[c] + "</option>");
    a += "</select>";
    b("#location-state-div").html(a);
    e.data("oldval", e.val())
    } else if ("AR" == this.value) {
    document.getElementById("location-state-div").style.display = "block";
    a = '<span class="spantxt">State:</span><select class="inputtxt" name="location-state" id="location-state">';
    b("#location-state-div").html();
    b("#location-state").val();
    for (c in h)a = void 0 == c ? a + ('<option value="' + c + '" selected="selected">' + h[c] + "</option>") : a +
    ('<option value="' + c + '">' + h[c] + "</option>");
    a += "</select>";
    b("#location-state-div").html(a);
    e.data("oldval", e.val())
    } else if ("JP" ==
this.value) {
    document.getElementById("location-state-div").style.display = "block";
    a = '<span class="spantxt">State:</span><select class="inputtxt" name="location-state" id="location-state">';
    b("#location-state-div").html();
    b("#location-state").val();
    for (c in m)a = void 0 == c ? a + ('<option value="' + c + '" selected="selected">' + m[c] + "</option>") : a +
    ('<option value="' + c + '">' + m[c] + "</option>");
    a += "</select>";
    b("#location-state-div").html(a);
    e.data("oldval", e.val())
    } else if ("ID" == this.value) {
    document.getElementById("location-state-div").style.display =
        "block";
    a = '<span class="spantxt">State:</span><select class="inputtxt" name="location-state" id="location-state">';
    b("#location-state-div").html();
    b("#location-state").val();
    for (c in l)a = void 0 == c ? a + ('<option value="' + c + '" selected="selected">' + l[c] + "</option>") : a +
    ('<option value="' + c + '">' + l[c] + "</option>");
    a += "</select>";
    b("#location-state-div").html(a);
    e.data("oldval", e.val())
    } else if ("IT" == this.value) {
    document.getElementById("location-state-div").style.display = "block";
    a = '<span class="spantxt">State:</span><select class="inputtxt" name="location-state" id="location-state">';
    b("#location-state-div").html();
    b("#location-state").val();
    for (c in k)a = void 0 == c ? a + ('<option value="' + c + '" selected="selected">' + k[c] + "</option>") : a +
    ('<option value="' + c + '">' + k[c] + "</option>");
    a += "</select>";
    b("#location-state-div").html(a);
    e.data("oldval", e.val())
    } else document.getElementById("location-state-div").style.display = "none"
})
});
function popupvalidation() {
    for (var b = document.getElementById("validfrm"), d = 0; d < b.elements.length; d++) {
    if ("location-country" == b.elements[d].id && 0 == b.elements[d].value || "" == b.elements[d].value.trim())return
    document.getElementById("textmatter").style.color = "#ff0000", b.elements[d].style.borderColor = "#ff0000", b.elements[d].focus(), !1;
    document.getElementById("textmatter").style.color = "#666666";
    b.elements[d].style.borderColor = "#E5E5E5";
    if ("SL_PHONE" == b.elements[d].id && !0 == isNaN(b.elements[d].value))return document.getElementById("textmatter").style.color =
    "#ff0000", b.elements[d].style.borderColor = "#ff0000", b.elements[d].focus(), !1;
    if ("SL_EMAIL" == b.elements[d].id) {
    var f = b.elements[d].value, g = f.indexOf("@"), h = f.lastIndexOf(".");
    if (1 > g || h < g + 2 || h + 2 >= f.length)return
    document.getElementById("textmatter").style.color = "#ff0000", b.elements[d].style.borderStyle = "solid",
    b.elements[d].style.borderColor = "#ff0000", b.elements[d].focus(), !1;
    document.getElementById("textmatter").style.color = "#666666";
    b.elements[d].style.borderColor = "#E5E5E5"
    }
}
return!0
}
;