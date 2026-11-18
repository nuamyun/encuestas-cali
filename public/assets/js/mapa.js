/**
 * Selector de ubicación sobre un mapa de Cali (HU-04).
 * - Clic en el mapa o arrastrar el marcador fija la latitud y longitud.
 * - "Ubicar en el mapa" busca la dirección con Nominatim (OpenStreetMap);
 *   si no la encuentra, intenta con el barrio y deja el marcador para ajustarlo.
 * - Rechaza puntos fuera de los límites de Cali (los mismos del servidor).
 */
window.MapaCali = (function () {
    'use strict';

    function selector(opts) {
        const cfg = opts.config;
        const limites = L.latLngBounds([cfg.latMin, cfg.lonMin], [cfg.latMax, cfg.lonMax]);
        const latInput = document.getElementById(opts.latInput);
        const lonInput = document.getElementById(opts.lonInput);
        const estado = document.getElementById(opts.estado);

        const mapa = L.map(opts.contenedor, { maxBounds: limites.pad(0.15), minZoom: 11, scrollWheelZoom: false })
            .setView([cfg.centroLat, cfg.centroLon], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(mapa);

        let marcador = null;
        let ultimaValida = null;

        function mostrar(texto, tipo) {
            estado.textContent = texto;
            estado.className = 'small mt-2 text-' + (tipo || 'secondary');
        }

        function actualizarCampos(punto) {
            latInput.value = punto.lat.toFixed(6);
            lonInput.value = punto.lng.toFixed(6);
            ultimaValida = punto;
            mostrar('Ubicación: ' + latInput.value + ', ' + lonInput.value, 'success');
        }

        function poner(punto, centrar) {
            const latlng = L.latLng(punto);
            if (!limites.contains(latlng)) {
                mostrar('Ese punto está fuera de Cali. Marca una ubicación dentro del municipio.', 'danger');
                return false;
            }
            if (!marcador) {
                marcador = L.marker(latlng, { draggable: true }).addTo(mapa);
                marcador.on('dragend', function () {
                    const p = marcador.getLatLng();
                    if (limites.contains(p)) {
                        actualizarCampos(p);
                    } else {
                        marcador.setLatLng(ultimaValida);
                        mostrar('Ese punto está fuera de Cali. El marcador volvió a la última ubicación válida.', 'danger');
                    }
                });
            } else {
                marcador.setLatLng(latlng);
            }
            actualizarCampos(latlng);
            if (centrar) {
                mapa.setView(latlng, 17);
            }
            return true;
        }

        function quitar() {
            if (marcador) {
                mapa.removeLayer(marcador);
                marcador = null;
            }
            latInput.value = '';
            lonInput.value = '';
            ultimaValida = null;
            mostrar('Sin georreferenciar. Haz clic en el mapa para marcar la ubicación.');
        }

        async function geocodificar(consulta) {
            const params = new URLSearchParams({
                format: 'jsonv2',
                q: consulta,
                countrycodes: 'co',
                viewbox: [cfg.lonMin, cfg.latMax, cfg.lonMax, cfg.latMin].join(','),
                bounded: '1',
                limit: '1'
            });
            const respuesta = await fetch('https://nominatim.openstreetmap.org/search?' + params, {
                headers: { 'Accept-Language': 'es' }
            });
            if (!respuesta.ok) {
                throw new Error('HTTP ' + respuesta.status);
            }
            const datos = await respuesta.json();
            return datos.length ? { lat: parseFloat(datos[0].lat), lng: parseFloat(datos[0].lon) } : null;
        }

        /**
         * @param {{texto: string, consulta: string}[]} intentos  de más preciso a menos preciso
         */
        async function buscar(intentos) {
            mostrar('Buscando en el mapa…');
            try {
                for (const intento of intentos) {
                    const punto = await geocodificar(intento.consulta);
                    if (punto && poner(punto, true)) {
                        mostrar(intento.texto + ' Revisa el marcador y arrástralo al sitio exacto si hace falta.', 'success');
                        return;
                    }
                }
                mostrar('No se encontró la dirección. Haz clic en el mapa para marcarla manualmente.', 'warning');
            } catch (e) {
                mostrar('No fue posible consultar el buscador de direcciones. Marca la ubicación manualmente.', 'warning');
            }
        }

        mapa.on('click', function (e) { poner(e.latlng, false); });

        if (latInput.value && lonInput.value) {
            poner([parseFloat(latInput.value), parseFloat(lonInput.value)], true);
        } else {
            mostrar('Sin georreferenciar. Haz clic en el mapa para marcar la ubicación.');
        }

        setTimeout(function () { mapa.invalidateSize(); }, 200);

        return { buscar: buscar, quitar: quitar };
    }

    /**
     * Convierte una dirección colombiana ("Calle 5 # 38-25") en un texto que
     * Nominatim entiende mejor ("Calle 5 38-25").
     */
    function limpiarDireccion(direccion) {
        return direccion.replace(/#|n[°º.]?\s*(?=\d)/gi, ' ').replace(/\s+/g, ' ').trim();
    }

    return { selector: selector, limpiarDireccion: limpiarDireccion };
})();
