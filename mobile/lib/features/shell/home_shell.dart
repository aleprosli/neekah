import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/l10n/l10n.dart';
import '../../core/notification_badge.dart';
import '../../ui/theme.dart';
import '../../ui/widgets/common.dart';

/// The five-tab frame: Utama, Tempahan, Kalendar, Enquiry, Lagi.
class HomeShell extends StatefulWidget {
  const HomeShell({required this.shell, super.key});

  final StatefulNavigationShell shell;

  @override
  State<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<HomeShell> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) {
        context.read<NotificationBadge>().refresh();
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final unread = context.watch<NotificationBadge>().unread;

    return Scaffold(
      body: widget.shell,
      bottomNavigationBar: DecoratedBox(
        decoration: const BoxDecoration(
          color: Colors.white,
          border: Border(top: BorderSide(color: NColors.lineSoft)),
          boxShadow: [BoxShadow(color: Color(0x0A2A1E1F), blurRadius: 16, offset: Offset(0, -4))],
        ),
        child: NavigationBar(
          selectedIndex: widget.shell.currentIndex,
          onDestinationSelected: (index) {
            HapticFeedback.selectionClick();
            widget.shell.goBranch(index, initialLocation: index == widget.shell.currentIndex);
          },
          destinations: [
            NavigationDestination(
              icon: const Icon(Icons.space_dashboard_outlined),
              selectedIcon: const Icon(Icons.space_dashboard_rounded),
              label: context.t('nav.home'),
            ),
            NavigationDestination(
              icon: const Icon(Icons.event_note_outlined),
              selectedIcon: const Icon(Icons.event_note_rounded),
              label: context.t('nav.bookings'),
            ),
            NavigationDestination(
              icon: const Icon(Icons.calendar_month_outlined),
              selectedIcon: const Icon(Icons.calendar_month_rounded),
              label: context.t('nav.calendar'),
            ),
            NavigationDestination(
              icon: const Icon(Icons.forum_outlined),
              selectedIcon: const Icon(Icons.forum_rounded),
              label: context.t('nav.enquiries'),
            ),
            NavigationDestination(
              icon: CountBadge(count: unread, child: const Icon(Icons.more_horiz_rounded)),
              selectedIcon: CountBadge(count: unread, child: const Icon(Icons.more_horiz_rounded)),
              label: context.t('nav.more'),
            ),
          ],
        ),
      ),
    );
  }
}
