<?php
/** UMS EduGate — layout footer (Phase 3 UI shell). Closes whichever shell header.php opened. */
if (($shell ?? 'app') === 'auth'): ?>
    </main>
<footer class="container py-4 small text-secondary text-center border-top">
  UMS EduGate · University Management System for Education Consultancy
</footer>
<?php else: ?>
      </div>
    </main>
    <footer class="border-top py-3 px-4 small text-secondary bg-body umg-footer">
      <div class="container-fluid d-flex flex-wrap justify-content-between gap-2" style="max-width: 1200px;">
        <span>UMS EduGate · University Management System for Education Consultancy</span>
        <span>Built per PRD · Phase 3 UI shell</span>
      </div>
    </footer>
  </div><!-- /.umg-content-col -->
</div><!-- /.umg-layout -->
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/app.js"></script>
</body>
</html>
