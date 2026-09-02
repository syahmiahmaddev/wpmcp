# Contributing to WP-MCP

Thank you for your interest in contributing to **WP-MCP**! We welcome bug fixes, documentation improvements, new tool adapters, and feature suggestions.

---

## 🛠 Development Workflow

1. **Fork & Clone**:
   ```bash
   git clone https://github.com/syahmiahmaddev/wpmcp.git
   cd wpmcp
   ```

2. **Branching**:
   Create a descriptive feature or fix branch:
   ```bash
   git checkout -b feat/my-new-tool
   # or
   git checkout -b fix/issue-description
   ```

3. **PHP Code Style & Standards**:
   - We target **PHP 8.0+** compatibility.
   - Follow standard [WordPress PHP Coding Standards (WPCS)](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/).
   - Always sanitize inputs (`sanitize_text_field`, `sanitize_key`, etc.) and escape outputs (`esc_html`, `esc_attr`).
   - Use capability checks (`current_user_can`) for all tools.

4. **Testing Your Changes**:
   - Ensure all PHP files pass linting without syntax errors:
     ```bash
     find . -name "*.php" ! -path "./vendor/*" -exec php -l {} \;
     ```
   - Test tool registration via `includes/tools/class-wpmcp-tool-registry.php`.

5. **Building Release Zip**:
   ```bash
   bash bin/build-release.sh
   ```

6. **Submit a Pull Request**:
   - Provide a clear summary of the changes and motivation.
   - Reference any related open issues.

---

## 📜 Code of Conduct
Be respectful, constructive, and helpful to fellow developers in issues, discussions, and pull requests.
