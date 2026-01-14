<?php
/**
 * Plugin Name: Steel.ee Price Calculator
 * Description: Kiir-hinnakalkulaator (materjal + haar A/B + jm) + WPForms hidden field tugi (ID=17)
 * Version: 1.2
 */

if (!defined('ABSPATH')) exit;

add_shortcode('steel_price_calc', function () {
  return '
  <div style="border:1px solid #ddd;padding:16px;border-radius:10px;max-width:520px">
    <h3 style="margin:0 0 12px 0">Arvuta orienteeruv hind</h3>

    <label style="display:block;margin-bottom:8px">Pikkus (mm) — haar A<br>
      <input id="spc_l" type="number" min="1" inputmode="numeric"
        style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px">
    </label>

    <label style="display:block;margin-bottom:8px">Laius (mm) — haar B<br>
      <input id="spc_w" type="number" min="1" inputmode="numeric"
        style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px">
    </label>

    <label style="display:block;margin-bottom:8px">Kogus (tk)<br>
      <input id="spc_q" type="number" min="1" value="1" inputmode="numeric"
        style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px">
    </label>

    <label style="display:block;margin-bottom:8px">Materjal<br>
      <select id="spc_mat" style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px">
        <option value="tsink">Tsink (7 €/m²)</option>
        <option value="alutsink">Alutsink (7 €/m²)</option>
        <option value="pol">POL (7.5 €/m²)</option>
        <option value="pur" selected>PUR (8 €/m²)</option>
        <option value="pur_matt">PUR MATT (11 €/m²)</option>
      </select>
    </label>

    <label style="display:block;margin-bottom:12px">Toon (RAL) (valikuline)<br>
      <input id="spc_ral" placeholder="nt RAL7016"
        style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px">
    </label>

    <button type="button" onclick="steelCalcPrice()"
      style="padding:10px 14px;border-radius:10px;border:0;cursor:pointer">
      Arvuta hind
    </button>

    <div style="margin-top:14px">
      <div id="spc_price" style="font-size:22px;font-weight:700">—</div>
      <div id="spc_breakdown" style="font-size:12px;opacity:.8;margin-top:6px;line-height:1.4"></div>
      <div style="font-size:12px;opacity:.65;margin-top:6px">
        Orienteeruv hind. Täpne hind kinnitatakse pakkumisel.
      </div>
    </div>

    <script>
    function steelCalcPrice(){
      var l = Number(document.getElementById("spc_l").value || 0);
      var w = Number(document.getElementById("spc_w").value || 0);
      var q = Number(document.getElementById("spc_q").value || 1);
      var mat = document.getElementById("spc_mat").value;
      var ral = (document.getElementById("spc_ral").value || "").trim();

      if(l<=0 || w<=0 || q<=0){
        document.getElementById("spc_price").innerHTML = "Palun sisesta mõõdud ja kogus";
        document.getElementById("spc_breakdown").innerHTML = "";
        return;
      }

      // Haar A ja Haar B (toorikule +10mm kummalegi)
      var haarA = l + 10; // mm
      var haarB = w + 10; // mm

      // Materjali m2 hinnad (sinu hinnad)
      var baseM2 = 8; // PUR default
      if(mat === "tsink")     baseM2 = 7;
      if(mat === "alutsink") baseM2 = 7;
      if(mat === "pol")      baseM2 = 7.5;
      if(mat === "pur")      baseM2 = 8;
      if(mat === "pur_matt") baseM2 = 11;

      // Arvutused
      var m2 = (haarA/1000) * (haarB/1000);  // tooriku pindala
      var jm = (haarA/1000);                 // jm haar A järgi
      var jmPrice = 3;                       // 3€ / jm

      var materialCost = (m2 * baseM2) * q;
      var jmCost = (jm * jmPrice) * q;

      var total = materialCost + jmCost;
      var price = total.toFixed(2);

      document.getElementById("spc_price").innerHTML = price.replace(".", ",") + " €";

      // Breakdown (et klient ja sina näeks detaili)
      var matName = "";
      if(mat==="tsink") matName="Tsink";
      if(mat==="alutsink") matName="Alutsink";
      if(mat==="pol") matName="POL";
      if(mat==="pur") matName="PUR";
      if(mat==="pur_matt") matName="PUR MATT";

      var breakdown =
        "Haar A: " + haarA + " mm, Haar B: " + haarB + " mm<br>" +
        "Toorik: " + m2.toFixed(4) + " m² × " + baseM2 + " €/m² × " + q + " tk = " + materialCost.toFixed(2) + " €<
