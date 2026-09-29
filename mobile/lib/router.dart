import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import 'core/auth/auth_controller.dart';
import 'features/auth/blocked_screen.dart';
import 'features/auth/login_screen.dart';
import 'features/auth/pro_lock_screen.dart';
import 'features/auth/splash_screen.dart';
import 'features/bookings/booking_detail_screen.dart';
import 'features/bookings/bookings_screen.dart';
import 'features/boost/boost_screen.dart';
import 'features/calendar/calendar_screen.dart';
import 'features/dashboard/dashboard_screen.dart';
import 'features/enquiries/enquiries_screen.dart';
import 'features/enquiries/enquiry_detail_screen.dart';
import 'features/more/more_screen.dart';
import 'features/notifications/notifications_screen.dart';
import 'features/points/points_screen.dart';
import 'features/settings/settings_screen.dart';
import 'features/shell/home_shell.dart';

final GlobalKey<NavigatorState> rootNavigatorKey = GlobalKey<NavigatorState>(debugLabel: 'root');

const _gateRoutes = {'/splash', '/login', '/locked', '/blocked'};

/// Routes, with the auth state deciding which gate (if any) to show.
GoRouter buildRouter(AuthController auth) => GoRouter(
  navigatorKey: rootNavigatorKey,
  initialLocation: '/splash',
  refreshListenable: auth,
  redirect: (context, state) {
    final location = state.matchedLocation;

    String? only(String route) => location == route ? null : route;

    return switch (auth.status) {
      AuthStatus.unknown => only('/splash'),
      AuthStatus.signedOut => only('/login'),
      AuthStatus.proRequired => only('/locked'),
      AuthStatus.blocked => only('/blocked'),
      AuthStatus.signedIn => _gateRoutes.contains(location) ? '/home' : null,
    };
  },
  routes: [
    GoRoute(path: '/splash', pageBuilder: (context, state) => _fade(state, const SplashScreen())),
    GoRoute(path: '/login', pageBuilder: (context, state) => _fade(state, const LoginScreen())),
    GoRoute(path: '/locked', pageBuilder: (context, state) => _fade(state, const ProLockScreen())),
    GoRoute(path: '/blocked', pageBuilder: (context, state) => _fade(state, const BlockedScreen())),
    StatefulShellRoute.indexedStack(
      pageBuilder: (context, state, shell) => _fade(state, HomeShell(shell: shell)),
      branches: [
        StatefulShellBranch(
          routes: [GoRoute(path: '/home', builder: (context, state) => const DashboardScreen())],
        ),
        StatefulShellBranch(
          routes: [GoRoute(path: '/bookings', builder: (context, state) => const BookingsScreen())],
        ),
        StatefulShellBranch(
          routes: [GoRoute(path: '/calendar', builder: (context, state) => const CalendarScreen())],
        ),
        StatefulShellBranch(
          routes: [GoRoute(path: '/enquiries', builder: (context, state) => const EnquiriesScreen())],
        ),
        StatefulShellBranch(
          routes: [GoRoute(path: '/more', builder: (context, state) => const MoreScreen())],
        ),
      ],
    ),
    GoRoute(
      path: '/booking/:reference',
      parentNavigatorKey: rootNavigatorKey,
      builder: (context, state) => BookingDetailScreen(reference: state.pathParameters['reference']!),
    ),
    GoRoute(
      path: '/enquiry/:id',
      parentNavigatorKey: rootNavigatorKey,
      builder: (context, state) => EnquiryDetailScreen(id: state.pathParameters['id']!),
    ),
    GoRoute(path: '/boost', parentNavigatorKey: rootNavigatorKey, builder: (context, state) => const BoostScreen()),
    GoRoute(path: '/points', parentNavigatorKey: rootNavigatorKey, builder: (context, state) => const PointsScreen()),
    GoRoute(path: '/notifications', parentNavigatorKey: rootNavigatorKey, builder: (context, state) => const NotificationsScreen()),
    GoRoute(path: '/settings', parentNavigatorKey: rootNavigatorKey, builder: (context, state) => const SettingsScreen()),
  ],
);

CustomTransitionPage<void> _fade(GoRouterState state, Widget child) => CustomTransitionPage<void>(
  key: state.pageKey,
  child: child,
  transitionDuration: const Duration(milliseconds: 350),
  transitionsBuilder: (context, animation, secondaryAnimation, child) => FadeTransition(
    opacity: CurvedAnimation(parent: animation, curve: Curves.easeOut),
    child: child,
  ),
);
