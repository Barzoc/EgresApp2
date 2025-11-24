    <footer class="main-footer">
        <div class="float-right d-none d-sm-block">
            <b>Version</b> 1.0
        </div>
    <!-- Footer text removed as requested -->
    </footer>
</div>
<!-- wrapper -->

<!-- REQUIRED SCRIPTS -->
<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/adminlte.min.js"></script>
<script src="../assets/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../assets/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="../assets/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="../assets/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="../assets/plugins/jszip/jszip.min.js"></script>
<script src="../assets/plugins/pdfmake/pdfmake.min.js"></script>
<script src="../assets/plugins/pdfmake/vfs_fonts.js"></script>
<script src="../assets/plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="../assets/plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="../assets/plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
<script src="../assets/plugins/sweetalert2/sweetalert2.min.js"></script>
<script src="../assets/plugins/chart.js/Chart.min.js"></script>

<!-- Chatbot assistant widget -->
<div id="chatbot-widget" class="chatbot">
    <button id="chatbot-toggle" class="chatbot-toggle" title="Asistente virtual">
        <img src="../assets/img/imagenes/chatbot_mascota.png.png" alt="Asistente" class="chatbot-toggle-face">
        <div class="chatbot-toggle-text">
            <span>¿Te ayudo?</span>
            <small>EgresApp Assistant</small>
        </div>
    </button>
    <div id="chatbot-panel" class="chatbot-panel hidden">
        <header>
            <div>
                <h6 class="mb-1">EgresApp Assistant</h6>
                <small>IA local para ayudarte</small>
            </div>
            <button id="chatbot-close" class="btn btn-sm btn-light"><i class="fas fa-times"></i></button>
        </header>
        <div class="chatbot-hero">
            <img src="../assets/img/imagenes/chatbot_mascota.png.png" alt="Mascota EgresApp">
        </div>
        <div id="chatbot-messages" class="chatbot-messages">
            <div class="chatbot-empty-state">Inicia la conversación o elige una acción rápida.</div>
        </div>
        <div id="chatbot-quick" class="chatbot-quick-actions"></div>
        <div class="chatbot-input">
            <textarea id="chatbot-text" placeholder="Escribe tu pregunta..."></textarea>
            <button id="chatbot-send"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
</div>

<script src="../assets/js/chatbot.js"></script>
</body>

</html>