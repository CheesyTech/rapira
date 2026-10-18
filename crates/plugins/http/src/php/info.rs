use std::sync::OnceLock;

use rapira_sapi::rapira_zval_stringl;

use super::*;

static SERVER: OnceLock<(String, u16)> = OnceLock::new();

/// The first call sets the server name and port for the process; a later call changes nothing.
pub(crate) fn set_server_identity(name: String, port: u16) {
    let _ = SERVER.set((name, port));
}

/// # Safety
/// `return_value` writable; engine active on this thread.
#[unsafe(no_mangle)]
pub unsafe extern "C" fn rapira_rs_http_server_name(return_value: *mut zval) -> bool {
    let Some((name, _)) = SERVER.get() else {
        return false;
    };
    unsafe {
        rapira_zval_stringl(
            return_value,
            zend::ptr_or_empty(name.as_bytes()),
            name.len(),
        )
    };
    true
}

#[unsafe(no_mangle)]
pub extern "C" fn rapira_rs_http_server_port() -> i64 {
    SERVER.get().map_or(0, |(_, p)| i64::from(*p))
}
