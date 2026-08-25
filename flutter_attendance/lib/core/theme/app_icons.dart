// ignore_for_file: constant_identifier_names
//
// Single source of truth for every icon used across the app.
// Existing call sites keep the old Material names (e.g. `AppIcons.home_rounded`)
// so a blanket `Icons.` → `AppIcons.` rename is safe, while the actual glyph
// comes from Phosphor for system-wide consistency.

import 'package:flutter/widgets.dart';
import 'package:phosphor_flutter/phosphor_flutter.dart';

class AppIcons {
  AppIcons._();

  // ── Navigation / chrome ───────────────────────────────────────────────────
  static const IconData home_rounded = PhosphorIconsFill.house;
  static const IconData home_outlined = PhosphorIconsRegular.house;
  static const IconData menu_rounded = PhosphorIconsRegular.list;
  static const IconData close = PhosphorIconsRegular.x;
  static const IconData close_rounded = PhosphorIconsRegular.x;
  static const IconData cancel = PhosphorIconsFill.xCircle;
  static const IconData cancel_rounded = PhosphorIconsFill.xCircle;
  static const IconData highlight_off_rounded = PhosphorIconsRegular.xCircle;
  static const IconData block_rounded = PhosphorIconsFill.prohibit;
  static const IconData arrow_back = PhosphorIconsRegular.arrowLeft;
  static const IconData arrow_back_rounded = PhosphorIconsRegular.arrowLeft;
  static const IconData arrow_back_ios_new_rounded =
      PhosphorIconsRegular.caretLeft;
  static const IconData arrow_forward_ios_rounded =
      PhosphorIconsRegular.caretRight;
  static const IconData chevron_right = PhosphorIconsRegular.caretRight;
  static const IconData chevron_right_rounded = PhosphorIconsRegular.caretRight;
  static const IconData keyboard_arrow_down_rounded =
      PhosphorIconsRegular.caretDown;
  static const IconData keyboard_arrow_up_rounded =
      PhosphorIconsRegular.caretUp;
  static const IconData expand_more_rounded = PhosphorIconsRegular.caretDown;
  static const IconData check_rounded = PhosphorIconsRegular.check;
  static const IconData open_in_new = PhosphorIconsRegular.arrowSquareOut;
  static const IconData open_in_new_rounded = PhosphorIconsFill.arrowSquareOut;
  static const IconData add = PhosphorIconsRegular.plus;
  static const IconData refresh = PhosphorIconsRegular.arrowClockwise;
  static const IconData refresh_rounded = PhosphorIconsRegular.arrowClockwise;
  static const IconData search_rounded = PhosphorIconsRegular.magnifyingGlass;
  static const IconData tune_rounded = PhosphorIconsRegular.slidersHorizontal;
  static const IconData tune_outlined = PhosphorIconsRegular.slidersHorizontal;
  static const IconData swap_horiz_rounded =
      PhosphorIconsRegular.arrowsLeftRight;
  static const IconData swap_vert_rounded = PhosphorIconsRegular.arrowsDownUp;

  // ── Status / feedback ─────────────────────────────────────────────────────
  static const IconData check_circle = PhosphorIconsFill.checkCircle;
  static const IconData check_circle_rounded = PhosphorIconsFill.checkCircle;
  static const IconData check_circle_outline = PhosphorIconsRegular.checkCircle;
  static const IconData check_circle_outline_rounded =
      PhosphorIconsRegular.checkCircle;
  static const IconData info_rounded = PhosphorIconsFill.info;
  static const IconData info_outline = PhosphorIconsRegular.info;
  static const IconData info_outline_rounded = PhosphorIconsRegular.info;
  static const IconData warning_rounded = PhosphorIconsFill.warning;
  static const IconData warning_amber_rounded = PhosphorIconsFill.warning;
  static const IconData error_outline = PhosphorIconsRegular.warningCircle;
  static const IconData error_outline_rounded =
      PhosphorIconsRegular.warningCircle;
  static const IconData report_problem_rounded =
      PhosphorIconsFill.warningOctagon;
  static const IconData report_problem_outlined =
      PhosphorIconsRegular.warningOctagon;
  static const IconData help_outline = PhosphorIconsRegular.question;
  static const IconData lightbulb_outline = PhosphorIconsRegular.lightbulb;
  static const IconData circle = PhosphorIconsFill.circle;
  static const IconData radio_button_checked = PhosphorIconsFill.circle;
  static const IconData radio_button_off = PhosphorIconsRegular.circle;

  // ── Auth / security ───────────────────────────────────────────────────────
  static const IconData lock_rounded = PhosphorIconsFill.lock;
  static const IconData lock_outline = PhosphorIconsRegular.lock;
  static const IconData lock_outline_rounded = PhosphorIconsRegular.lock;
  static const IconData lock_reset_rounded = PhosphorIconsFill.lockKey;
  static const IconData lock_reset_outlined = PhosphorIconsRegular.lockKey;
  static const IconData vpn_key_rounded = PhosphorIconsFill.key;
  static const IconData password_rounded = PhosphorIconsFill.password;
  static const IconData fingerprint_rounded = PhosphorIconsFill.fingerprint;
  static const IconData fingerprint_outlined = PhosphorIconsRegular.fingerprint;
  static const IconData security_rounded = PhosphorIconsFill.shieldCheck;
  static const IconData security_outlined = PhosphorIconsRegular.shieldCheck;
  static const IconData verified_rounded = PhosphorIconsFill.sealCheck;
  static const IconData verified_outlined = PhosphorIconsRegular.sealCheck;
  static const IconData verified_user_rounded = PhosphorIconsFill.shieldCheck;
  static const IconData visibility = PhosphorIconsFill.eye;
  static const IconData visibility_rounded = PhosphorIconsFill.eye;
  static const IconData visibility_off = PhosphorIconsFill.eyeSlash;
  static const IconData visibility_off_rounded = PhosphorIconsFill.eyeSlash;
  static const IconData logout_rounded = PhosphorIconsRegular.signOut;

  // ── Editing / actions ─────────────────────────────────────────────────────
  static const IconData edit_rounded = PhosphorIconsFill.pencilSimple;
  static const IconData edit_note_rounded = PhosphorIconsFill.notePencil;
  static const IconData save_rounded = PhosphorIconsFill.floppyDisk;
  static const IconData send_rounded = PhosphorIconsFill.paperPlaneTilt;
  static const IconData delete_outline_rounded = PhosphorIconsRegular.trash;
  static const IconData download_rounded = PhosphorIconsFill.downloadSimple;
  static const IconData upload_file_rounded = PhosphorIconsFill.uploadSimple;
  static const IconData share_rounded = PhosphorIconsFill.shareNetwork;
  static const IconData link = PhosphorIconsRegular.link;
  static const IconData link_rounded = PhosphorIconsFill.link;
  static const IconData language = PhosphorIconsRegular.globe;
  static const IconData add_comment_rounded = PhosphorIconsFill.chatCircleText;

  // ── People / profile ──────────────────────────────────────────────────────
  static const IconData person = PhosphorIconsFill.user;
  static const IconData person_rounded = PhosphorIconsFill.user;
  static const IconData person_outline = PhosphorIconsRegular.user;
  static const IconData person_outline_rounded = PhosphorIconsRegular.user;
  static const IconData person_off_rounded = PhosphorIconsFill.userMinus;
  static const IconData person_search_outlined = PhosphorIconsRegular.userFocus;
  static const IconData people_outline = PhosphorIconsRegular.users;
  static const IconData groups_rounded = PhosphorIconsFill.users;
  static const IconData groups_outlined = PhosphorIconsRegular.users;
  static const IconData how_to_reg_rounded = PhosphorIconsFill.userCheck;
  static const IconData how_to_reg_outlined = PhosphorIconsRegular.userCheck;
  static const IconData face_rounded = PhosphorIconsFill.smiley;
  static const IconData man_rounded = PhosphorIconsFill.user;
  static const IconData woman_rounded = PhosphorIconsFill.user;
  static const IconData wc_rounded = PhosphorIconsRegular.toilet;
  static const IconData family_restroom_rounded = PhosphorIconsFill.usersFour;
  static const IconData family_restroom_outlined =
      PhosphorIconsRegular.usersFour;
  static const IconData badge_rounded = PhosphorIconsFill.identificationBadge;
  static const IconData badge_outlined =
      PhosphorIconsRegular.identificationBadge;

  // ── Education / academic ──────────────────────────────────────────────────
  static const IconData school_rounded = PhosphorIconsFill.graduationCap;
  static const IconData school_outlined = PhosphorIconsRegular.graduationCap;
  static const IconData menu_book = PhosphorIconsRegular.book;
  static const IconData menu_book_rounded = PhosphorIconsFill.book;
  static const IconData menu_book_outlined = PhosphorIconsRegular.book;
  static const IconData library_books_rounded = PhosphorIconsFill.books;
  static const IconData library_books_outlined = PhosphorIconsRegular.books;
  static const IconData local_library_rounded = PhosphorIconsFill.books;
  static const IconData local_library_outlined = PhosphorIconsRegular.books;
  static const IconData cast_for_education_rounded = PhosphorIconsFill.monitor;
  static const IconData cast_for_education_outlined =
      PhosphorIconsRegular.monitor;
  static const IconData history_edu_rounded = PhosphorIconsFill.bookOpenText;
  static const IconData grade_rounded = PhosphorIconsFill.star;
  static const IconData grade_outlined = PhosphorIconsRegular.star;

  // ── Assignments / assessments ─────────────────────────────────────────────
  static const IconData assignment = PhosphorIconsRegular.clipboardText;
  static const IconData assignment_outlined =
      PhosphorIconsRegular.clipboardText;
  static const IconData assignment_rounded = PhosphorIconsFill.clipboardText;
  static const IconData assignment_turned_in_rounded = PhosphorIconsFill.checks;
  static const IconData assignment_turned_in_outlined = PhosphorIconsRegular.checks;
  static const IconData fact_check_rounded = PhosphorIconsFill.checkSquare;
  static const IconData fact_check_outlined = PhosphorIconsRegular.checkSquare;
  static const IconData rule_folder_rounded = PhosphorIconsFill.folderOpen;
  static const IconData quiz_rounded = PhosphorIconsFill.question;
  static const IconData quiz_outlined = PhosphorIconsRegular.question;
  static const IconData assessment_outlined = PhosphorIconsRegular.chartBar;
  static const IconData playlist_add_check_rounded =
      PhosphorIconsFill.checkSquare;
  static const IconData playlist_add_check_outlined =
      PhosphorIconsRegular.checkSquare;
  static const IconData playlist_add_check_circle_rounded =
      PhosphorIconsFill.checkCircle;
  static const IconData playlist_add_check_circle_outlined =
      PhosphorIconsRegular.checkCircle;

  // ── Data / content ────────────────────────────────────────────────────────
  static const IconData article_rounded = PhosphorIconsFill.article;
  static const IconData description_rounded = PhosphorIconsFill.fileText;
  static const IconData description_outlined = PhosphorIconsRegular.fileText;
  static const IconData notes_rounded = PhosphorIconsFill.notepad;
  static const IconData notes_outlined = PhosphorIconsRegular.notepad;
  static const IconData inventory_2_rounded = PhosphorIconsFill.package;
  static const IconData summarize_rounded = PhosphorIconsFill.listBullets;
  static const IconData table_chart_rounded = PhosphorIconsFill.table;
  static const IconData insights_rounded = PhosphorIconsFill.chartLineUp;
  static const IconData trending_up_rounded = PhosphorIconsRegular.trendUp;
  static const IconData track_changes_rounded = PhosphorIconsFill.target;
  static const IconData track_changes_outlined = PhosphorIconsRegular.target;
  static const IconData timeline_rounded = PhosphorIconsRegular.chartLine;
  static const IconData history_rounded =
      PhosphorIconsFill.clockCounterClockwise;
  static const IconData history_outlined =
      PhosphorIconsRegular.clockCounterClockwise;
  static const IconData folder = PhosphorIconsRegular.folder;
  static const IconData folder_rounded = PhosphorIconsFill.folder;
  static const IconData folder_open_rounded = PhosphorIconsFill.folderOpen;
  static const IconData folder_open_outlined = PhosphorIconsRegular.folderOpen;
  static const IconData insert_drive_file = PhosphorIconsRegular.file;
  static const IconData picture_as_pdf = PhosphorIconsFill.filePdf;
  static const IconData video_file = PhosphorIconsFill.fileVideo;

  // ── Communication ─────────────────────────────────────────────────────────
  static const IconData forum_rounded = PhosphorIconsFill.chatsCircle;
  static const IconData forum_outlined = PhosphorIconsRegular.chatsCircle;
  static const IconData campaign = PhosphorIconsFill.megaphone;
  static const IconData campaign_rounded = PhosphorIconsFill.megaphone;
  static const IconData campaign_outlined = PhosphorIconsRegular.megaphone;
  static const IconData contact_mail_outlined =
      PhosphorIconsRegular.envelopeOpen;
  static const IconData email_outlined = PhosphorIconsRegular.envelope;
  static const IconData local_post_office_rounded = PhosphorIconsFill.envelope;
  static const IconData call_outlined = PhosphorIconsRegular.phone;
  static const IconData phone_iphone_rounded = PhosphorIconsFill.deviceMobile;
  static const IconData rate_review_rounded =
      PhosphorIconsFill.chatCenteredText;
  static const IconData rate_review_outlined =
      PhosphorIconsRegular.chatCenteredText;
  static const IconData notifications_none_rounded = PhosphorIconsRegular.bell;

  // ── Finance ───────────────────────────────────────────────────────────────
  static const IconData paid_rounded = PhosphorIconsFill.money;
  static const IconData payments_rounded = PhosphorIconsFill.creditCard;
  static const IconData payments_outlined = PhosphorIconsRegular.creditCard;
  static const IconData account_balance_rounded = PhosphorIconsFill.bank;
  static const IconData account_balance_wallet_rounded =
      PhosphorIconsFill.wallet;
  static const IconData account_balance_wallet_outlined =
      PhosphorIconsRegular.wallet;
  static const IconData currency_exchange_rounded =
      PhosphorIconsRegular.currencyCircleDollar;
  static const IconData receipt_rounded = PhosphorIconsFill.receipt;
  static const IconData receipt_outlined = PhosphorIconsRegular.receipt;
  static const IconData receipt_long_rounded = PhosphorIconsFill.receipt;
  static const IconData receipt_long_outlined = PhosphorIconsRegular.receipt;
  static const IconData request_quote_rounded = PhosphorIconsFill.fileText;
  static const IconData request_quote_outlined = PhosphorIconsRegular.fileText;

  // ── Calendar / time ───────────────────────────────────────────────────────
  static const IconData calendar_month_rounded = PhosphorIconsFill.calendar;
  static const IconData calendar_month_outlined = PhosphorIconsRegular.calendar;
  static const IconData calendar_today_outlined = PhosphorIconsRegular.calendar;
  static const IconData calendar_today_rounded = PhosphorIconsFill.calendar;
  static const IconData calendar_view_week_rounded =
      PhosphorIconsFill.calendarBlank;
  static const IconData calendar_view_week_outlined =
      PhosphorIconsRegular.calendarBlank;
  static const IconData event_available_rounded =
      PhosphorIconsFill.calendarCheck;
  static const IconData event_busy_rounded = PhosphorIconsFill.calendarX;
  static const IconData today_rounded = PhosphorIconsFill.calendarDot;
  static const IconData schedule = PhosphorIconsRegular.clock;
  static const IconData schedule_rounded = PhosphorIconsFill.clock;
  static const IconData hourglass_empty_rounded =
      PhosphorIconsRegular.hourglass;
  static const IconData hourglass_top_rounded = PhosphorIconsFill.hourglassHigh;
  static const IconData timelapse_rounded = PhosphorIconsRegular.timer;
  static const IconData pending_outlined = PhosphorIconsRegular.clockClockwise;

  // ── Location ──────────────────────────────────────────────────────────────
  static const IconData place_outlined = PhosphorIconsRegular.mapPin;
  static const IconData location_on_outlined = PhosphorIconsRegular.mapPin;
  static const IconData location_city_outlined = PhosphorIconsRegular.buildings;
  static const IconData map_outlined = PhosphorIconsRegular.mapTrifold;
  static const IconData wb_sunny_rounded = PhosphorIconsFill.sun;
  static const IconData wb_sunny_outlined = PhosphorIconsRegular.sun;
  static const IconData room_rounded = PhosphorIconsRegular.door;
  static const IconData meeting_room_rounded = PhosphorIconsFill.door;

  // ── QR / scanning ─────────────────────────────────────────────────────────
  static const IconData qr_code_rounded = PhosphorIconsFill.qrCode;
  static const IconData qr_code_outlined = PhosphorIconsRegular.qrCode;
  static const IconData qr_code_2_rounded = PhosphorIconsFill.qrCode;
  static const IconData qr_code_2_outlined = PhosphorIconsRegular.qrCode;
  static const IconData qr_code_scanner_rounded = PhosphorIconsRegular.qrCode;
  static const IconData flash_on_rounded = PhosphorIconsFill.lightning;
  static const IconData flash_off_rounded = PhosphorIconsFill.lightningSlash;
  static const IconData camera_alt_rounded = PhosphorIconsFill.camera;
  static const IconData camera_alt_outlined = PhosphorIconsRegular.camera;
  static const IconData photo_library_rounded = PhosphorIconsFill.imagesSquare;
  static const IconData broken_image_outlined =
      PhosphorIconsRegular.imageBroken;

  // ── Misc ──────────────────────────────────────────────────────────────────
  static const IconData play_arrow_rounded = PhosphorIconsFill.play;
  static const IconData favorite_rounded = PhosphorIconsFill.heart;
  static const IconData favorite_border_rounded = PhosphorIconsRegular.heart;
  static const IconData bookmark_rounded = PhosphorIconsFill.bookmarkSimple;
  static const IconData inbox_rounded = PhosphorIconsFill.tray;
  static const IconData inbox_outlined = PhosphorIconsRegular.tray;
  static const IconData confirmation_number_rounded = PhosphorIconsFill.ticket;
  static const IconData local_activity_rounded = PhosphorIconsFill.ticket;
  static const IconData construction_rounded = PhosphorIconsFill.wrench;
  static const IconData construction_outlined = PhosphorIconsRegular.wrench;
  static const IconData work_outline_rounded = PhosphorIconsRegular.briefcase;
  static const IconData system_update_alt_rounded =
      PhosphorIconsFill.arrowClockwise;
  static const IconData android_rounded = PhosphorIconsFill.androidLogo;
  static const IconData cloud_sync_rounded = PhosphorIconsFill.cloudArrowUp;
  static const IconData cloud_off_rounded = PhosphorIconsFill.cloudSlash;
  static const IconData wifi_off_rounded = PhosphorIconsFill.wifiSlash;
}
