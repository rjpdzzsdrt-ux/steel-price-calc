<?php
/**
 * Plugin Name: Steel.ee Price Calculator
 * Description: Hinnakalkulaator (materjal + haar A/B + jm) + WPForms päringu täitmine (hind ID=17, parameetrid steel-orient-params, sõnum auto)
 * Version: 2.3
 */

if (!defined('ABSPATH')) exit;

add_shortcode('steel_price_calc', function () {
  return '
  <div style="border:1px solid #ddd;padding:16px;border-radius:10px;max-width:520px">
    <h3 style="margin:0 0 6px 0">Saa hind 30 sekundiga – sisesta mõõdud</h3>
    <div style="font-size:13px;opacity:.75;margin-bottom:12px">
      Orienteeruv hind kohe ekraanile. Soovi korral saada päring – lisame mõõdud automaatselt kaasa.
    </div>

    <label style="display:block;margin-bottom:8px">Pikkus (mm) — haar A<br>
      <input id="spc_l" type="number" min="1" inputmode="numeric"
        style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px">
    </label>

    <label style="display:block;margin-bottom:8px">Laius (mm) — haar B<br>
      <input id="spc_w" type="number" min="1" inputmode="numeric"
        style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px">
    </label>

    <label style="display:block;margin-bottom:8px">Kogus (jm)<br>
      <input id="spc_q" type="number" min="0.01" step="0.01" value="1" inputmode="decimal"
        style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px">
    </label>

    <label style="display:block;margin-bottom:8px">Materjal<br>
      <select id="spc_mat" style="width:100%;padding:10px;border:1px solid #ccc;border-radius:8px">
        <option value="tsink">Tsink</option>
        <option value="alutsink">Alutsink</option>
        <option value="pol">POL</option>
        <option value="pur" selected>PUR</option>
        <option value="pur_matt">PUR MATT</option>
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
      <div style="font-size:12px;opacity:.65;margin-top:6px">
        Orienteeruv hind. Täpne hind kinnitatakse pakkumisel.
      </div>
    </div>

    <script>
    function steelFindWPFormsMessageField(){
      // Otsime lehelt kõige tõenäolisema WPForms "message" textarea/input välja.
      // Täidame ainult siis, kui väli on tühi.
      var candidates = [];

      // 1) WPForms textarea (enamasti sõnum)
      document.querySelectorAll("form.wpforms-form textarea").forEach(function(t){ candidates.push(t); });

      // 2) Üldine textarea (kui vorm pole wpforms-form klassiga)
      document.querySelectorAll("textarea").forEach(function(t){ candidates.push(t); });

      // 3) Mõnikord kasutatakse input type=text sõnumiks
      document.querySelectorAll("form.wpforms-form input[type=\'text\']").forEach(function(i){ candidates.push(i); });

      // Vali esimene, mis tundub sõnum: name sisaldab message/textarea või on suurem
      for (var i=0;i<candidates.length;i++){
        var el = candidates[i];
        var name = (el.getAttribute("name") || "").toLowerCase();
        var id = (el.getAttribute("id") || "").toLowerCase();
        if(name.indexOf("message")>-1 || name.indexOf("textarea")>-1 || id.indexOf("message")>-1){
          return el;
        }
      }

      // fallback: esimene textarea WPForms vormis
      for (var j=0;j<candidates.length;j++){
        if(candidates[j].tagName.toLowerCase()==="textarea") return candidates[j];
      }
      return null;
    }

    function steelSetMessageIfEmpty(text){
      var msg = steelFindWPFormsMessageField();
      if(!msg) return;

      var current = (msg.value || "").trim();
      if(current.length > 0) return; // ära kirjuta üle

      msg.value = text;
      msg.dispatchEvent(new Event("input", {bubbles:true}));
      msg.dispatchEvent(new Event("change", {bubbles:true}));
    }

    function steelCalcPrice(){
      var l = Number(document.getElementById("spc_l").value || 0);
      var w = Number(document.getElementById("spc_w").value || 0);

      // Kogus (jm)
      var q = Number(document.getElementById("spc_q").value || 0);
      var mat = document.getElementById("spc_mat").value;
      var ral = (document.getElementById("spc_ral").value || "").trim();

      if(l<=0 || w<=0 || q<=0){
        document.getElementById("spc_price").innerHTML = "Palun sisesta mõõdud ja kogus";
        return;
      }

      // Haar A ja Haar B: kummalegi +10mm (toorik)
      var haarA = l + 10; // mm
      var haarB = w + 10; // mm

      // Materjali m2 hinnad (sinu hinnad)
      var baseM2 = 8; // PUR default
      if(mat === "tsink")     baseM2 = 7;
      if(mat === "alutsink") baseM2 = 7;
      if(mat === "pol")      baseM2 = 7.5;
      if(mat === "pur")      baseM2 = 8;
      if(mat === "pur_matt") baseM2 = 11;

      // JM hind on sisemine (klient ei näe)
      var jmPrice = 3; // €/jm

      // Arvutused
      var m2 = (haarA/1000) * (haarB/1000);
      var jm = (haarA/1000);

      var materialCost = (m2 * baseM2) * q;
      var jmCost = (jm * jmPrice) * q;
      var total = materialCost + jmCost;

      var price = total.toFixed(2);
      document.getElementById("spc_price").innerHTML = price.replace(".", ",") + " €";

      // Materjali nimi parameetritesse
      var matName = "";
      if(mat==="tsink") matName="Tsink";
      if(mat==="alutsink") matName="Alutsink";
      if(mat==="pol") matName="POL";
      if(mat==="pur") matName="PUR";
      if(mat==="pur_matt") matName="PUR MATT";

      // Parameetrid JSON (WPFormsile)
      var paramsObj = {
        pikkus_mm: l,
        laius_mm: w,
        kogus_jm: q,
        haarA_mm: haarA,
        haarB_mm: haarB,
        materjal: matName,
        ral: ral,
        hind_eur: Number(price)
      };
      var params = JSON.stringify(paramsObj);

      // ✅ WPForms: hind Hidden Field ID = 17 (täida KÕIK sobivad väljad lehel)
      var wpformsPriceId = 17;
      document.querySelectorAll(
        \'input[name="wpforms[fields][\' + wpformsPriceId + \']"], textarea[name="wpforms[fields][\' + wpformsPriceId + \']"]\'
      ).forEach(function(el){
        el.value = price;
        el.dispatchEvent(new Event("input", {bubbles:true}));
        el.dispatchEvent(new Event("change", {bubbles:true}));
      });

      // ✅ WPForms: parameetrid Hidden field CSS class steel-orient-params
      document.querySelectorAll(
        "input.steel-orient-params, textarea.steel-orient-params, .steel-orient-params input, .steel-orient-params textarea"
      ).forEach(function(el){
        el.value = params;
        el.dispatchEvent(new Event("input", {bubbles:true}));
        el.dispatchEvent(new Event("change", {bubbles:true}));
      });

      // ✅ Varuvariant: hind CSS class steel-orient-hind
      document.querySelectorAll(
        "input.steel-orient-hind, textarea.steel-orient-hind, .steel-orient-hind input, .steel-orient-hind textarea"
      ).forEach(function(el){
        el.value = price;
        el.dispatchEvent(new Event("input", {bubbles:true}));
        el.dispatchEvent(new Event("change", {bubbles:true}));
      });

      // ✅ Täida automaatselt sõnum (kui tühi)
      var msgText =
        "Soovin pakkumist.\\n" +
        "Pikkus: " + l + " mm, Laius: " + w + " mm\\n" +
        "Kogus: " + q + " jm\\n" +
        "Materjal: " + matName + (ral ? (", RAL: " + ral) : "") + "\\n" +
        "Orienteeruv hind: " + price.replace(".", ",") + " €";
      steelSetMessageIfEmpty(msgText);
    }
    </script>
  </div>';
});
